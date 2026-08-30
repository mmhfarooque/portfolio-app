<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers admin.media.upload, the endpoint behind the Editor.js image tool on
 * the About page. The Vue side has always called route('admin.media.upload'),
 * but the route and controller method did not exist, so Ziggy threw during
 * editor init and the upload was dead.
 */
class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function png(int $w = 60, int $h = 40): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocate($im, 10, 120, 200));
        $tmp = tempnam(sys_get_temp_dir(), 'upl') . '.png';
        imagepng($im, $tmp);
        imagedestroy($im);

        return new UploadedFile($tmp, 'shot.png', 'image/png', null, true);
    }

    public function test_guests_cannot_upload(): void
    {
        $this->post(route('admin.media.upload'), ['image' => $this->png()])
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_upload_an_image_and_gets_the_editorjs_shape(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('admin.media.upload'), ['image' => $this->png()]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'file' => ['url']]);
        $this->assertSame(1, $response->json('success'));

        // The stored file must be the re-encoded WebP, not the original PNG.
        $url = $response->json('file.url');
        $this->assertStringContainsString('/storage/editorjs/', $url);
        $this->assertStringEndsWith('.webp', $url);

        $path = storage_path('app/public/editorjs/' . basename($url));
        $this->assertFileExists($path);
        $this->assertSame('image/webp', mime_content_type($path));

        @unlink($path);
    }

    public function test_a_non_image_is_rejected(): void
    {
        $user = User::factory()->create();
        $tmp = tempnam(sys_get_temp_dir(), 'bad') . '.txt';
        file_put_contents($tmp, '<?php echo "not an image";');

        $this->actingAs($user)
            ->post(
                route('admin.media.upload'),
                ['image' => new UploadedFile($tmp, 'payload.txt', 'text/plain', null, true)]
            )
            ->assertSessionHasErrors('image');
    }

    public function test_the_field_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.media.upload'), [])
            ->assertSessionHasErrors('image');
    }

    public function test_a_wide_image_is_scaled_down_to_1920(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('admin.media.upload'), ['image' => $this->png(2400, 800)]);

        $response->assertOk();
        $path = storage_path('app/public/editorjs/' . basename($response->json('file.url')));
        [$w] = getimagesize($path);
        $this->assertSame(1920, $w);

        @unlink($path);
    }
}
