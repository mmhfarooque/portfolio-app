<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\NewsletterSubscriber;
use App\Models\Photo;
use App\Models\PhotoComment;
use App\Models\PhotoLike;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WeeklyStatsEmail extends Command
{
    protected $signature = 'stats:weekly {--email= : Override recipient email}';
    protected $description = 'Send weekly stats digest email to admin';

    public function handle(): int
    {
        $email = $this->option('email') ?: Setting::get('contact_email', 'farooque7@gmail.com');
        $since = now()->subWeek();

        // Gather stats
        $stats = [
            'period' => $since->format('M d') . ' – ' . now()->format('M d, Y'),
            'total_views' => (int) Photo::sum('views'),
            'views_this_week' => (int) DB::table('photos')
                ->where('updated_at', '>=', $since)
                ->sum(DB::raw('views')),
            'top_photos' => Photo::published()
                ->orderByDesc('views')
                ->take(5)
                ->get(['title', 'slug', 'views']),
            'new_likes' => PhotoLike::where('created_at', '>=', $since)->count(),
            'total_likes' => PhotoLike::count(),
            'new_comments' => PhotoComment::where('created_at', '>=', $since)->count(),
            'pending_comments' => PhotoComment::where('status', 'pending')->count(),
            'new_subscribers' => NewsletterSubscriber::where('created_at', '>=', $since)
                ->whereNotNull('verified_at')
                ->count(),
            'total_subscribers' => NewsletterSubscriber::whereNotNull('verified_at')
                ->whereNull('unsubscribed_at')
                ->count(),
            'new_contacts' => Contact::where('created_at', '>=', $since)->count(),
            'unread_contacts' => Contact::where('status', 'new')->count(),
            'total_photos' => Photo::published()->count(),
        ];

        // Build HTML email
        $html = $this->buildEmail($stats);

        Mail::html($html, function ($message) use ($email, $stats) {
            $message->to($email)
                ->subject("Weekly Stats — {$stats['period']} — mfaruk.com");
        });

        $this->info("Weekly stats sent to {$email}");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Views', number_format($stats['total_views'])],
                ['New Likes', $stats['new_likes']],
                ['New Comments', $stats['new_comments']],
                ['Pending Comments', $stats['pending_comments']],
                ['New Subscribers', $stats['new_subscribers']],
                ['New Contacts', $stats['new_contacts']],
            ]
        );

        return self::SUCCESS;
    }

    private function buildEmail(array $stats): string
    {
        $topPhotosHtml = '';
        foreach ($stats['top_photos'] as $i => $photo) {
            $num = $i + 1;
            $views = number_format($photo->views);
            $topPhotosHtml .= "<tr><td style='padding:6px 12px;border-bottom:1px solid #333;color:#ccc;'>{$num}. {$photo->title}</td><td style='padding:6px 12px;border-bottom:1px solid #333;text-align:right;color:#d4a853;font-weight:600;'>{$views}</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background-color:#0d0d0d;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:40px 20px;">

    <!-- Header -->
    <div style="text-align:center;margin-bottom:32px;">
        <h1 style="color:#d4a853;font-size:24px;margin:0;">Weekly Stats</h1>
        <p style="color:#888;font-size:14px;margin:8px 0 0;">{$stats['period']}</p>
    </div>

    <!-- Stats Grid -->
    <div style="background:#1a1a1a;border-radius:12px;padding:24px;margin-bottom:20px;border:1px solid #333;">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="padding:12px;text-align:center;border-right:1px solid #333;">
                    <div style="font-size:28px;font-weight:700;color:#d4a853;">{$stats['new_likes']}</div>
                    <div style="font-size:12px;color:#888;margin-top:4px;">New Likes</div>
                </td>
                <td style="padding:12px;text-align:center;border-right:1px solid #333;">
                    <div style="font-size:28px;font-weight:700;color:#d4a853;">{$stats['new_comments']}</div>
                    <div style="font-size:12px;color:#888;margin-top:4px;">New Comments</div>
                </td>
                <td style="padding:12px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#d4a853;">{$stats['new_subscribers']}</div>
                    <div style="font-size:12px;color:#888;margin-top:4px;">New Subscribers</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Action Items -->
    <div style="background:#1a1a1a;border-radius:12px;padding:20px;margin-bottom:20px;border:1px solid #333;">
        <h3 style="color:#fff;font-size:14px;margin:0 0 12px;text-transform:uppercase;letter-spacing:1px;">Needs Attention</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:8px 0;color:#ccc;">Pending comments</td><td style="text-align:right;color:{$this->alertColor($stats['pending_comments'])};font-weight:600;">{$stats['pending_comments']}</td></tr>
            <tr><td style="padding:8px 0;color:#ccc;">Unread contacts</td><td style="text-align:right;color:{$this->alertColor($stats['unread_contacts'])};font-weight:600;">{$stats['unread_contacts']}</td></tr>
            <tr><td style="padding:8px 0;color:#ccc;">New contact messages</td><td style="text-align:right;color:#ccc;font-weight:600;">{$stats['new_contacts']}</td></tr>
        </table>
    </div>

    <!-- Top Photos -->
    <div style="background:#1a1a1a;border-radius:12px;padding:20px;margin-bottom:20px;border:1px solid #333;">
        <h3 style="color:#fff;font-size:14px;margin:0 0 12px;text-transform:uppercase;letter-spacing:1px;">Top Photos (All Time)</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            {$topPhotosHtml}
        </table>
    </div>

    <!-- Totals -->
    <div style="background:#1a1a1a;border-radius:12px;padding:20px;margin-bottom:20px;border:1px solid #333;">
        <h3 style="color:#fff;font-size:14px;margin:0 0 12px;text-transform:uppercase;letter-spacing:1px;">Portfolio Totals</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:8px 0;color:#ccc;">Published photos</td><td style="text-align:right;color:#ccc;font-weight:600;">{$stats['total_photos']}</td></tr>
            <tr><td style="padding:8px 0;color:#ccc;">Total views</td><td style="text-align:right;color:#ccc;font-weight:600;">{$this->formatNumber($stats['total_views'])}</td></tr>
            <tr><td style="padding:8px 0;color:#ccc;">Total likes</td><td style="text-align:right;color:#ccc;font-weight:600;">{$stats['total_likes']}</td></tr>
            <tr><td style="padding:8px 0;color:#ccc;">Active subscribers</td><td style="text-align:right;color:#ccc;font-weight:600;">{$stats['total_subscribers']}</td></tr>
        </table>
    </div>

    <!-- Footer -->
    <div style="text-align:center;margin-top:32px;">
        <a href="https://mfaruk.com/dashboard" style="display:inline-block;padding:12px 24px;background:#d4a853;color:#000;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;">Open Dashboard</a>
        <p style="color:#555;font-size:12px;margin-top:16px;">Sent from mfaruk.com weekly digest</p>
    </div>

</div>
</body>
</html>
HTML;
    }

    private function alertColor(int $count): string
    {
        return $count > 0 ? '#ef4444' : '#22c55e';
    }

    private function formatNumber(int $num): string
    {
        return number_format($num);
    }
}
