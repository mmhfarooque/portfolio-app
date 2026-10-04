<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * /resume is password-gated. These tests run against a made-up resume
 * (tests never contain the real content) built by `resume:import` into a
 * temporary directory.
 */
class ResumePageTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'test-pass-4821';

    private const PHONE = '+61 400 111 222';

    private const EXPERIENCE_TEXT = 'Shipped the fictional widget platform';

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->dir = sys_get_temp_dir().'/resume-test-'.uniqid();
        mkdir($this->dir);

        file_put_contents($this->dir.'/resume-master.md', self::fixtureMarkdown());

        config([
            'resume.source' => $this->dir.'/resume-master.md',
            'resume.path' => $this->dir.'/resume.json',
            'resume.password_hash' => Hash::make(self::PASSWORD),
            'resume.password_hint' => self::PASSWORD,
            'resume.show_password_hint' => true,
        ]);

        Artisan::call('resume:import');
        RateLimiter::clear('resume-unlock');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        @rmdir($this->dir);

        parent::tearDown();
    }

    public function test_locked_page_shows_only_the_form(): void
    {
        $response = $this->get('/resume');

        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Resume/Locked')
                ->where('summary.name', 'Alex Example')
                ->where('summary.email', config('resume.contact_email'))
                ->missing('resume')
            );

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $html = $response->getContent();
        $this->assertStringNotContainsString(self::PHONE, $html);
        $this->assertStringNotContainsString(self::EXPERIENCE_TEXT, $html);
        $this->assertStringNotContainsString('Example Widgets Pty Ltd', $html);
    }

    public function test_wrong_password_shows_an_error_and_stays_locked(): void
    {
        $this->from('/resume')->post('/resume/unlock', ['password' => 'nope'])
            ->assertRedirect('/resume')
            ->assertSessionHasErrors('password');

        $this->get('/resume')->assertInertia(fn (Assert $page) => $page->component('Public/Resume/Locked'));
    }

    public function test_correct_password_unlocks_the_content(): void
    {
        $this->from('/resume')->post('/resume/unlock', ['password' => self::PASSWORD])
            ->assertRedirect('/resume')
            ->assertSessionHasNoErrors();

        $response = $this->get('/resume');

        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Resume/Show')
                ->where('resume.header.phone.label', self::PHONE.' (WhatsApp)')
                ->where('resume.header.phone.whatsapp', '61400111222')
                ->has('resume.experience', 2)
                ->has('resume.aiShowcase', 4)
            );

        $this->assertStringContainsString(self::EXPERIENCE_TEXT, $response->getContent());
    }

    public function test_unlock_expires(): void
    {
        $this->post('/resume/unlock', ['password' => self::PASSWORD]);

        $this->travel(config('resume.unlock_minutes') + 1)->minutes();

        $this->get('/resume')->assertInertia(fn (Assert $page) => $page->component('Public/Resume/Locked'));
    }

    public function test_unlock_is_csrf_protected(): void
    {
        $this->assertContains(
            'web',
            Route::getRoutes()->getByName('resume.unlock')->gatherMiddleware(),
            'resume.unlock must run in the web group (session + CSRF).'
        );
    }

    public function test_throttle_kicks_in_after_five_bad_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/resume')->post('/resume/unlock', ['password' => 'wrong-'.$i]);
        }

        // The sixth attempt is refused even with the right password.
        $this->from('/resume')->post('/resume/unlock', ['password' => self::PASSWORD])
            ->assertSessionHasErrors(['password' => 'Too many attempts. Please wait a minute and try again.']);

        $this->get('/resume')->assertInertia(fn (Assert $page) => $page->component('Public/Resume/Locked'));
    }

    public function test_there_is_no_download_route_and_no_pdf_link(): void
    {
        $this->assertFalse(Route::has('resume.download'));
        $this->get('/resume/download')->assertNotFound();

        $this->post('/resume/unlock', ['password' => self::PASSWORD]);
        $html = $this->get('/resume')->getContent();

        $this->assertDoesNotMatchRegularExpression('/\.pdf\b/i', $html);
        $this->assertStringNotContainsString('download', strtolower(json_encode(
            json_decode(file_get_contents(config('resume.path')), true)
        )));
    }

    public function test_hint_follows_the_env_flag(): void
    {
        $this->get('/resume')->assertInertia(fn (Assert $page) => $page->where('hint', self::PASSWORD));

        config(['resume.show_password_hint' => false]);

        $this->get('/resume')->assertInertia(fn (Assert $page) => $page->where('hint', null));
    }

    public function test_resume_is_kept_out_of_robots_sitemap_and_ziggy(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /resume');

        $this->assertStringNotContainsString('/resume', $this->get('/sitemap.xml')->getContent());

        $ziggy = (new \Tighten\Ziggy\Ziggy)->toArray()['routes'];
        $this->assertArrayNotHasKey('resume.show', $ziggy);
        $this->assertArrayNotHasKey('resume.unlock', $ziggy);
    }

    public function test_private_files_are_git_ignored(): void
    {
        foreach (['storage/app/private/resume-master.md', 'storage/app/private/resume.json'] as $path) {
            $result = Process::path(base_path())->run(['git', 'check-ignore', '-q', $path]);
            $this->assertSame(0, $result->exitCode(), "{$path} must be git-ignored");
        }
    }

    public function test_import_produces_valid_json_with_every_section(): void
    {
        $this->assertSame(0, Artisan::call('resume:import'));

        $data = json_decode(file_get_contents(config('resume.path')), true, flags: JSON_THROW_ON_ERROR);

        foreach (['header', 'profile', 'skills', 'experience', 'aiShowcase', 'projects', 'clientRange', 'freelance', 'education', 'languages'] as $key) {
            $this->assertArrayHasKey($key, $data);
            $this->assertNotEmpty($data[$key], "{$key} should not be empty");
        }

        $this->assertSame('Alex Example', $data['header']['name']);
        $this->assertSame('https://github.com/example/demo', $data['header']['github']['url']);
        $this->assertTrue($data['experience'][1]['concurrent']);
        $this->assertCount(4, $data['aiShowcase'][0]['steps']);
        $this->assertSame('Elementor', $data['projects'][2]['items'][0]['builder']);
    }

    public function test_ai_cards_come_from_the_markdown(): void
    {
        $cards = json_decode(file_get_contents(config('resume.path')), true)['aiShowcase'];

        $this->assertSame(['Helpdesk card', 'Migration card', 'Skills card', 'Loop card'], array_column($cards, 'title'));
        $this->assertStringStartsWith('Moved the sample sites', $cards[1]['body']);
        $this->assertSame([], $cards[1]['steps']);
    }

    public function test_import_fails_when_an_ai_card_phrase_is_not_found(): void
    {
        file_put_contents(config('resume.source'), str_replace('from: Wrote a set of', 'from: Nothing starts like this', self::fixtureMarkdown()));

        $this->assertSame(1, Artisan::call('resume:import'));
    }

    public function test_import_fails_loudly_when_a_section_is_missing(): void
    {
        file_put_contents(config('resume.source'), str_replace('## Profile', '## Bio', self::fixtureMarkdown()));

        $this->assertSame(1, Artisan::call('resume:import'));
    }

    private static function fixtureMarkdown(): string
    {
        $phone = self::PHONE;
        $experience = self::EXPERIENCE_TEXT;

        return <<<MD
# Alex Example — Detailed Resume

**Engineer · Example Stack**

Sampletown (remote) · alex@example.test · {$phone} (WhatsApp) · [linkedin.com/in/alex-example](https://www.linkedin.com/in/alex-example) · [example.test](https://example.test)

## Profile

First profile paragraph.

Second profile paragraph.

## Core skills

- **Testing:** PHPUnit, Pest, Dusk. Wrote a very long claim that should stay whole as one chip.

## Experience

**Lead Engineer** · Example Widgets Pty Ltd, Sampletown · January 2020 – March 2024

Engaged directly from 2020.

- {$experience} for example.com.au clients.
- Built tooling. Wired up the review loop: tickets arrive with a suggestion.
- Set up a sorting pipeline: it collects parcels, scans labels, prints summaries, and, once checked, ships the order.
- Moved the sample sites between two hosts.
- Wrote a set of 3 scripts that tidy things.

**Contractor** · Side Co, Elsewhere · June 2021 – May 2022 (alongside Example Widgets)

- Did side work.

## AI Engineering

Card notes.

- **Helpdesk card** · from: Set up a sorting pipeline · steps
- **Migration card** · from: Moved the sample sites
- **Skills card** · from: Wrote a set of
- **Loop card** · from: Wired up the review loop

## Selected projects and client range

Intro to the projects.

**Government, education and enterprise**

| Site | Organisation |
| --- | --- |
| gov.example.gov.au | Example Government (theme work) |

**Multi-domain builds**

- A network across example-one.com.au and example-two.com.au.

**E-commerce (WordPress and WooCommerce)**

| Store | Products | Builder |
| --- | --- | --- |
| shop.example.com.au | Widgets. Built from scratch. | Elementor |

Also: other.example.com.

**Agency partners and own work**

- [demo](https://example.test): own demo. Code: [github.com/example/demo](https://github.com/example/demo).

Plus 10 further sites.

## Freelance record

Example freelance record.

## Education and certification

- BSc · Example University · 2001–2004

**Languages:** English (native)
MD;
    }
}
