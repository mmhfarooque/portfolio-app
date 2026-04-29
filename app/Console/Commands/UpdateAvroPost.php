<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * One-shot content update for the ibus-avro-fixed blog post.
 *
 * Original v2.4.0 post (2026-04-14) was GNOME-Wayland-focused. Now that
 * v2.5.x ships with KDE Plasma 6 support, the post needs new sections,
 * updated SEO metadata, and additional tags. This command makes that
 * update via deploy (rather than touching the live DB by hand).
 *
 * Idempotent — running it twice produces the same end state.
 *
 * Usage:
 *   php artisan posts:update-avro
 *   php artisan posts:update-avro --dry-run   # preview without writing
 */
class UpdateAvroPost extends Command
{
    protected $signature = 'posts:update-avro {--dry-run : Print intended changes without saving}';
    protected $description = 'Update the ibus-avro-fixed blog post with v2.5.x KDE Plasma support content';

    private const SLUG = 'fixing-avro-phonetic-ubuntu-wayland-left-shift-bug';

    public function handle(): int
    {
        $post = Post::where('slug', self::SLUG)->first();
        if (! $post) {
            $this->error("Post with slug '" . self::SLUG . "' not found.");
            return self::FAILURE;
        }

        $newTitle = 'Fixing Avro Phonetic on Linux Wayland — Left Shift, GNOME, KDE Plasma';
        $newExcerpt = "Avro Phonetic for Linux had a 14-year-old bug that broke the Left Shift key, and Wayland broke input switching entirely. I forked it, fixed the Shift bug, ported the install to GNOME 50+ and KDE Plasma 6, built a GTK4 manager, and shipped it as `ibus-avro-fixed` — one install for both desktops.";
        $newSeoTitle = 'Fix Avro Phonetic on Linux Wayland — Left Shift, GNOME 50, KDE Plasma 6';
        $newMetaDescription = 'How I fixed the 14-year-old Left Shift bug in ibus-avro, Wayland input switching on GNOME 50 (Mutter) and KDE Plasma 6 (kglobalaccel), and packaged it all as a one-command installer with a GTK4 GUI manager.';
        $newContent = $this->postContent();
        $newTags = [
            'linux', 'ubuntu', 'kubuntu', 'open-source', 'bangla', 'bengali',
            'ibus', 'ibus-avro', 'avro-phonetic', 'wayland', 'gnome',
            'gnome-wayland', 'kde', 'kde-plasma', 'kde-wayland', 'kglobalaccel',
            'gtk4', 'python', 'github', 'keyboard', 'input-method',
            'shift-key-bug', 'ubuntu-24-04', 'ubuntu-26-04',
        ];

        if ($this->option('dry-run')) {
            $this->line('--- DRY RUN — no changes written ---');
            $this->line('title:            ' . $newTitle);
            $this->line('excerpt:          ' . Str::limit($newExcerpt, 120));
            $this->line('seo_title:        ' . $newSeoTitle);
            $this->line('meta_description: ' . Str::limit($newMetaDescription, 120));
            $this->line('content length:   ' . strlen($newContent) . ' chars');
            $this->line('tags:             ' . count($newTags) . ' (' . implode(', ', $newTags) . ')');
            return self::SUCCESS;
        }

        $post->title = $newTitle;
        $post->excerpt = $newExcerpt;
        $post->seo_title = $newSeoTitle;
        $post->meta_description = $newMetaDescription;
        $post->content = $newContent;
        $post->save();
        $this->info("Updated post #{$post->id} '" . self::SLUG . "'.");

        // Ensure all tags exist; sync the post's tag list
        $tagIds = collect($newTags)->map(function (string $name) {
            return Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        });
        $post->tags()->sync($tagIds);
        $this->info("Synced " . $tagIds->count() . " tags.");

        return self::SUCCESS;
    }

    /**
     * The new HTML content for the post. Style-matched to the original:
     * <h2> section headings, <p> body, <strong>/<code>/<pre> as in the
     * v2.4.0 version. Markdown-free; this is what the admin UI stores.
     */
    private function postContent(): string
    {
        return <<<'HTML'
<h2>The Problem</h2>
<p>I type Bangla on Linux using Avro Phonetic — the most popular phonetic typing method for Bengali. You type English letters and it transliterates them to Bangla in real time. Type <code>ami bangla likhte pari</code> and you get আমি বাংলা লিখতে পারি.
The Linux version, <a href="https://github.com/sarim/ibus-avro">ibus-avro</a>, was written in 2012 for X11. It worked fine then. By 2026, on modern Linux with Wayland — whether GNOME or KDE Plasma — it was broken in ways that made daily use painful.
<strong>The Left Shift bug</strong> was the worst. When Avro was active, your Left Shift key stopped working — not just for Bangla, but everywhere. You could not type capital letters, you could not select text with Shift+click, you could not use Shift+anything. The Right Shift key had a similar issue. It sounds like a minor annoyance until you realise you use Shift hundreds of times a day.
<strong>Wayland broke input switching.</strong> The old ibus-avro used X11 key grabs to let you toggle between English and Bangla with a keyboard shortcut. On Wayland, X11 key grabs do not work. Different desktops handle this differently — GNOME funnels everything through Mutter, KDE funnels everything through KWin — and ibus-avro knew about neither.
<strong>GNOME 50+ broke it further.</strong> Starting with GNOME 50, the desktop no longer sets <code>GTK_IM_MODULE=ibus</code> automatically. GTK apps did not know to use IBus, so even after switching to Bangla, you would type English. The indicator said Bangla but nothing happened.
<strong>The preferences window crashed.</strong> The old preferences UI was GTK3. On modern GNOME with GTK4, it either crashed or rendered as a broken mess.
The upstream repo has been unmaintained since 2023. Issues pile up, pull requests sit there. I decided to fork it and fix everything — and then a few months later, when I migrated my workstation to Kubuntu 26.04 / Plasma 6.6 Wayland, I had to fix it all over again for KDE.</p>

<h2>The Left Shift Fix</h2>
<p>The root cause was one line in <code>main-gjs.js</code>, the IBus engine:</p>
<pre><code class="language-javascript">// capture the shift key
if (keycode == 42) { return true; }
</code></pre>
<p><code>return true</code> in an IBus engine means &quot;I consumed this key event — do not pass it to the OS.&quot; Keycode 42 is Left Shift. So when Avro was active, it intercepted every Left Shift press and swallowed it. The OS never saw it.
The fix is embarrassingly simple:</p>
<pre><code class="language-javascript">// Pass through Left Shift (42) and Right Shift (54)
if (keycode == 42 || keycode == 54) {
    return false;
}
</code></pre>
<p><code>return false</code> means &quot;I did not handle this — pass it through.&quot; I also added Right Shift (keycode 54) since it had the same problem.
This bug existed since the first commit in 2012. It is the kind of bug where you can see exactly what happened: someone was debugging modifier key handling, captured Shift to test something, and the <code>return true</code> never got changed back. Fourteen years.</p>

<h2>Wayland Input Switching — GNOME vs KDE</h2>
<p>On X11, IBus registered global keyboard shortcuts using <code>XGrabKey</code>. You pressed the shortcut, X11 intercepted it and told IBus to switch engines. Clean and simple.
On Wayland, there is no equivalent. Wayland deliberately does not let applications grab global keyboard shortcuts — that is a security decision. The desktop environment owns the keyboard, and each environment routes hotkeys differently. So &quot;configure Super+Space&quot; means two completely different things on GNOME and KDE.</p>

<h3>GNOME (Mutter)</h3>
<p>GNOME handles input switching natively through gsettings. The fix is to use GNOME&#039;s own keybinding schema:</p>
<pre><code class="language-bash">gsettings set org.gnome.desktop.wm.keybindings switch-input-source &quot;[&#039;&lt;Super&gt;space&#039;]&quot;
gsettings set org.gnome.desktop.wm.keybindings switch-input-source-backward &quot;[&#039;&lt;Shift&gt;&lt;Super&gt;space&#039;]&quot;
</code></pre>
<p>And clear the old IBus trigger that does nothing on Wayland but can cause conflicts:</p>
<pre><code class="language-bash">gsettings set org.freedesktop.ibus.general.hotkey trigger &quot;[&#039;&#039;]&quot;
</code></pre>
<p>Mutter intercepts the key combination at the compositor level and switches the input source for you.</p>

<h3>KDE Plasma 6 (KWin)</h3>
<p>KDE was a different beast entirely. The first instinct — set IBus&#039;s own <code>org.freedesktop.ibus.general.hotkey trigger</code> schema — turns out to be wrong: that schema is X11-era. It relies on X keygrabs that do not exist on Wayland. The daemon stores the value but cannot enforce it. KWin meanwhile does not intercept Super+Space the way Mutter does (KRunner is on Alt+Space in Plasma 6, not Super+Space).
The right fix on KDE is to bind Meta+Space at the desktop level via <code>kglobalaccel</code> — KDE&#039;s own global shortcut daemon — pointed at a small toggle script:</p>
<pre><code class="language-bash"># /usr/local/bin/ibus-avro-toggle
case &quot;$(ibus engine)&quot; in
    ibus-avro) ibus engine xkb:us::eng ;;
    *)         ibus engine ibus-avro ;;
esac
</code></pre>
<p>Then drop a <code>.desktop</code> file pointing at the script and register the shortcut live with <code>org.kde.KGlobalAccel.setShortcut</code> via DBus:</p>
<pre><code class="language-bash">gdbus call --session --dest org.kde.kglobalaccel \
    --object-path /kglobalaccel \
    --method org.kde.KGlobalAccel.setShortcut \
    &quot;[&#039;com.github.mmhfarooque.ibus-avro-toggle.desktop&#039;,&#039;_launch&#039;,...]&quot; \
    &#039;[268435488]&#039; 4
</code></pre>
<p>The magic number <code>268435488</code> is the Qt-encoded keycode for Meta+Space (<code>Qt::MetaModifier 0x10000000 | Qt::Key_Space 0x20</code>). Use <code>setShortcut</code> (signature <code>ai</code>) — the newer <code>setShortcutKeys</code> (signature <code>a(ai)</code>) is easy to crash with bad GVariant. KWin restarting itself in front of you is not a fun debugging session.
KDE Plasma 6 also requires <code>kwinrc [Wayland] InputMethod=…IBus.Panel.Wayland.Gtk3.desktop</code> so KWin actually attaches IBus to its input dispatch. Without that key, IBus is up but disconnected from the typing pipeline. The installer writes both pieces, calls <code>qdbus6 org.kde.KWin /KWin reconfigure</code>, and tells the user to log out and log back in once — Plasma&#039;s Virtual Keyboard service only re-attaches at session start.</p>

<h2>The GNOME 50 Environment Variable Fix</h2>
<p>This was the most confusing one to debug. After installing and fixing everything on GNOME, Avro would show as active in the system tray — Super+Space worked, the indicator said &quot;Bangla&quot; — but typing in any GTK app still produced English text.
The issue: starting with GNOME 50 on Wayland, the desktop environment handles IBus natively but does not export the <code>GTK_IM_MODULE=ibus</code> environment variable. Older GNOME versions set this automatically. Without it, GTK applications do not know to use IBus for text input.
The fix on GNOME: create <code>~/.config/environment.d/10-ibus-avro.conf</code> with:</p>
<pre><code class="language-ini">GTK_IM_MODULE=ibus
QT_IM_MODULE=ibus
XMODIFIERS=@im=ibus
</code></pre>
<p>This is the systemd user environment directory — variables here are loaded on login for all user sessions. Works on Wayland, does not break X11.
<strong>On KDE Plasma 6, do not do this.</strong> Plasma 6 uses the Wayland <code>text-input-v3</code> protocol natively; the IM module env vars are redundant, and setting them triggers an IBus startup notification asking the user to <em>unset</em> them. The installer skips the env file on KDE.</p>

<h2>The KDE-Specific Gotchas</h2>
<p>Three more things I hit on Kubuntu 26.04 / Plasma 6.6.4 that I did not see on GNOME:
<strong>Wrong systemd unit silently fails.</strong> The IBus systemd user unit comes in two flavours: <code>org.freedesktop.IBus.session.GNOME.service</code> (which has <code>Requisite=gnome-session-initialized.target</code> — that target only exists on GNOME) and <code>org.freedesktop.IBus.session.generic.service</code> (which has <code>Conflicts=gnome-session-initialized.target</code> and is the explicit non-GNOME variant). The early KDE branch of my installer used the GNOME unit. On KDE it returned &quot;Unit gnome-session-initialized.target not found&quot; and the daemon never actually started, but every other indicator showed green. Now branched by <code>XDG_CURRENT_DESKTOP</code>.
<strong>ibus-gtk3 / ibus-gtk4 are not pulled by ibus-avro.</strong> They are Recommends, not Depends, and apt&#039;s default config skips Recommends in some setups. Without them, GTK apps log &quot;No IM module matching GTK_IM_MODULE=ibus found&quot; and silently type English. The installer now installs them explicitly.
<strong>Kubuntu 26.04 ships a duplicate kglobalaccel service unit.</strong> Both <code>plasma-kglobalaccel.service</code> (Plasma 6) and <code>plasma-kglobalaccel5.service</code> (legacy Plasma 5) ship in the package, declaring the same DBus name. The .5 unit fails to load with &quot;File exists,&quot; and any DBus auto-activation cascades into a kwin restart. Workaround: <code>systemctl --user mask plasma-kglobalaccel5.service</code>. Worth reporting upstream to Kubuntu, but the installer&#039;s troubleshooting docs document it.</p>

<h2>Making Fixes Survive Updates</h2>
<p>Here is a problem with patching system files: <code>apt upgrade</code> replaces them. Every time Ubuntu pushes an ibus-avro update, the Left Shift fix gets overwritten and you are back to a broken keyboard.
The solution is an APT hook:</p>
<pre><code class="language-bash"># /etc/apt/apt.conf.d/99-fix-ibus-avro
DPkg::Post-Invoke { &quot;/usr/local/bin/fix-ibus-avro.sh || true&quot;; };
</code></pre>
<p>After every <code>dpkg</code> operation, it runs a script that checks if the fix is still applied and re-applies it if not. You install system updates, the fix survives. Right now this is Debian-only — Fedora/Arch/openSUSE would need <code>dnf</code>/<code>pacman</code>/<code>zypper</code> equivalents, which is a future port.</p>

<h2>The GUI Manager — IBus Avro Manager</h2>
<p>After the CS9711 fingerprint project, I knew the value of a GUI for this kind of tool. The command line is fine for the initial install, but checking what is fixed, updating, troubleshooting — that should be visual.
The IBus Avro Manager is a GTK4/libadwaita app in Python. The name was deliberately chosen to surface in both KDE Kickoff (search &quot;ibus&quot;) and GNOME overview (search &quot;avro&quot;). It shows:</p>
<ul>
<li><strong>Status</strong>: IBus daemon running or not, Avro engine loaded, Wayland or X11 session, configured input sources</li>
<li><strong>Fix dashboard</strong>: which fixes are applied (green) and which are missing (red), with a one-click &quot;Apply All Fixes&quot; button (now available in the header bar too, so it is always reachable)</li>
<li><strong>Input switching</strong>: current shortcut and a button to configure Super+Space — DE-aware, runs the right backend for GNOME or KDE automatically</li>
<li><strong>Typing settings</strong>: preview window, dictionary, max suggestions — reads and writes the gsettings in real time</li>
<li><strong>Self-update</strong>: checks GitHub for new versions, shows what changed, one-click update</li>
<li><strong>Diagnostics</strong>: activity log viewer with copy-to-clipboard for bug reporting</li>
</ul>

<h2>Bugs I Hit Along the Way</h2>
<p><strong>GTK3/GTK4 conflict</strong> — The original <code>main-gjs.js</code> imported <code>pref.js</code> at module level. The preferences window used GTK3. The new one uses GTK4. When IBus loaded the engine, it would try to load both GTK3 and GTK4 into the same process, which crashes GJS. The fix: never import pref.js in the engine. Launch preferences as a separate process via <code>GLib.spawn_command_line_async()</code>.
<strong>Debug log spam</strong> — The upstream code had <code>print()</code> calls on every single keypress. With Avro active, your system journal filled up with thousands of &quot;key pressed: 65, state: 0&quot; lines per minute. The installer comments out all debug prints except the critical &quot;IBus bus not found&quot; error.
<strong>Broken autostart desktop entry</strong> — An early version had <code>Exec=bash -c &quot;...\&quot;[&#039;&lt;Super&gt;space&#039;]\&quot;...&quot;</code> in an autostart .desktop file. The escaped quotes violate the desktop-entry spec, and systemd-xdg-autostart-generator silently rejected the file on every boot. Replaced with a real shell script invocation.
<strong>setShortcutKeys crashed kglobalaccel</strong> — Bad GVariant syntax for <code>a(ai)</code> caused the kglobalaccel service to disconnect from DBus, which cascaded into a kwin restart in front of me. Switched to <code>setShortcut</code> (signature <code>ai</code>), which is simpler and stable.
<strong>Total uninstall meant total uninstall</strong> — The first version of <code>uninstall.sh</code> ran <code>apt install --reinstall ibus-avro</code> to &quot;restore upstream,&quot; which left the IBus tray icon, the registered engine, and all base packages in place. Useless for fresh-install testing on the same machine. Now <code>uninstall.sh</code> purges every IBus apt package, kills running daemons, wipes user state, unbinds the kglobalaccel action, and removes its own source directory. After it finishes, <code>which ibus</code> returns nothing.</p>

<h2>Current State</h2>
<p>The project is at v2.5.x. One command to install:</p>
<pre><code class="language-bash">git clone https://github.com/mmhfarooque/ibus-avro-fixed.git ~/ibus-avro-fixed &amp;&amp; cd ~/ibus-avro-fixed &amp;&amp; bash install.sh
</code></pre>
<p>Verified on Kubuntu 26.04 LTS / Plasma 6.6.4 Wayland. Code path preserved (and previously verified) on Ubuntu 26.04 / GNOME 50+ Wayland. Should work on Debian, Mint, Pop!_OS, KDE Neon — same Debian-based code path, but I have not personally smoke-tested those on the v2.5.x line. Fedora, Arch, and openSUSE need a port (the patches are distro-agnostic; the install scripts use <code>apt</code>).
After the install finishes, log out and log back in once. Then right-click the IBus tray icon → Preferences → Input Method → Add → Bangla → Avro Phonetic. Press Super+Space. Type. The Shift key works. Bangla appears.
If you type Bangla on Linux and your Shift key does not work, or you switched to Wayland and lost your input switching shortcut, this fixes it on both major desktops:
<a href="https://github.com/mmhfarooque/ibus-avro-fixed">github.com/mmhfarooque/ibus-avro-fixed</a></p>
HTML;
    }
}
