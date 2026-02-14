<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Services\LoggingService;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = EmailTemplate::with('editor')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn($template) => [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'description' => $template->description,
                'subject' => $template->subject,
                'category' => $template->category,
                'is_active' => $template->is_active,
                'updated_at' => $template->updated_at->format('M j, Y g:i A'),
                'editor_name' => $template->editor?->name,
            ])
            ->groupBy('category');

        return Inertia::render('Admin/EmailTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        return Inertia::render('Admin/EmailTemplates/Edit', [
            'template' => [
                'id' => $emailTemplate->id,
                'slug' => $emailTemplate->slug,
                'name' => $emailTemplate->name,
                'description' => $emailTemplate->description,
                'subject' => $emailTemplate->subject,
                'body_html' => $emailTemplate->body_html,
                'available_variables' => $emailTemplate->available_variables ?? [],
                'category' => $emailTemplate->category,
                'is_active' => $emailTemplate->is_active,
                'updated_at' => $emailTemplate->updated_at->format('M j, Y g:i A'),
            ],
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update([
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'is_active' => $validated['is_active'] ?? $emailTemplate->is_active,
            'last_edited_by' => Auth::id(),
        ]);

        LoggingService::activity(
            'email_template.updated',
            "Email template '{$emailTemplate->name}' updated",
            $emailTemplate
        );

        return back()->with('success', 'Template updated successfully.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $sampleVariables = $this->getSampleVariables($emailTemplate);

        $renderedSubject = $emailTemplate->renderSubject($sampleVariables);
        $renderedBody = $emailTemplate->renderBody($sampleVariables);

        $html = view('emails.template', ['body' => $renderedBody])->render();

        return response()->json([
            'subject' => $renderedSubject,
            'html' => $html,
        ]);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        $adminEmail = Auth::user()->email;
        $sampleVariables = $this->getSampleVariables($emailTemplate);

        $renderedSubject = '[TEST] ' . $emailTemplate->renderSubject($sampleVariables);
        $renderedBody = $emailTemplate->renderBody($sampleVariables);

        try {
            Mail::to($adminEmail)->send(new TemplateMail(
                $renderedSubject,
                $renderedBody,
                $emailTemplate->slug,
                Auth::user()->name,
            ));

            LoggingService::activity(
                'email_template.test_sent',
                "Test email sent for template '{$emailTemplate->name}' to {$adminEmail}",
                $emailTemplate
            );

            return back()->with('success', "Test email sent to {$adminEmail}.");
        } catch (\Exception $e) {
            EmailLog::create([
                'template_slug' => $emailTemplate->slug,
                'recipient_email' => $adminEmail,
                'recipient_name' => Auth::user()->name,
                'subject' => $renderedSubject,
                'body_html' => $renderedBody,
                'status' => 'failed',
                'sent_by' => Auth::id(),
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    public function reset(EmailTemplate $emailTemplate)
    {
        $seeder = new EmailTemplateSeeder();
        $seeder->run();

        $emailTemplate->refresh();

        LoggingService::activity(
            'email_template.reset',
            "Email template '{$emailTemplate->name}' reset to default",
            $emailTemplate
        );

        return back()->with('success', 'Template reset to default.');
    }

    public function compose(): Response
    {
        $templates = EmailTemplate::active()
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'subject', 'body_html', 'available_variables']);

        return Inertia::render('Admin/EmailTemplates/Compose', [
            'templates' => $templates,
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'recipient_name' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
        ]);

        try {
            Mail::to($validated['recipient_email'])->send(new TemplateMail(
                $validated['subject'],
                $validated['body_html'],
                null,
                $validated['recipient_name'],
            ));

            LoggingService::activity(
                'email.custom_sent',
                "Custom email sent to {$validated['recipient_email']}",
            );

            return back()->with('success', "Email sent to {$validated['recipient_email']}.");
        } catch (\Exception $e) {
            EmailLog::create([
                'template_slug' => null,
                'recipient_email' => $validated['recipient_email'],
                'recipient_name' => $validated['recipient_name'],
                'subject' => $validated['subject'],
                'body_html' => $validated['body_html'],
                'status' => 'failed',
                'sent_by' => Auth::id(),
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function logs(Request $request): Response
    {
        $query = EmailLog::with('sender')->latest('sent_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->through(fn($log) => [
            'id' => $log->id,
            'template_slug' => $log->template_slug,
            'recipient_email' => $log->recipient_email,
            'recipient_name' => $log->recipient_name,
            'subject' => $log->subject,
            'body_html' => $log->body_html,
            'status' => $log->status,
            'error_message' => $log->error_message,
            'sender_name' => $log->sender?->name,
            'sent_at' => $log->sent_at?->format('M j, Y g:i A'),
        ]);

        return Inertia::render('Admin/EmailTemplates/Logs', [
            'logs' => $logs,
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    public function toggleActive(EmailTemplate $emailTemplate)
    {
        $emailTemplate->update([
            'is_active' => !$emailTemplate->is_active,
            'last_edited_by' => Auth::id(),
        ]);

        $status = $emailTemplate->is_active ? 'activated' : 'deactivated';

        LoggingService::activity(
            'email_template.toggled',
            "Email template '{$emailTemplate->name}' {$status}",
            $emailTemplate
        );

        return back()->with('success', "Template {$status}.");
    }

    protected function getSampleVariables(EmailTemplate $emailTemplate): array
    {
        $samples = [
            'user_name' => 'John Doe',
            'login_url' => config('app.url') . '/login',
            'site_name' => config('app.name'),
            'order_number' => 'ORD-12345678',
            'order_total' => '$99.99',
            'payment_method' => 'Credit Card',
            'items_list' => '<ul><li>Sample Photo License - $49.99</li><li>Premium Print 16x20 - $50.00</li></ul>',
            'dashboard_url' => config('app.url') . '/dashboard',
            'item_count' => '2',
            'refund_amount' => '$49.99',
            'reason' => 'Customer request',
            'product_name' => 'Premium Photo License',
            'variant_name' => 'Standard License',
            'days_remaining' => '7',
            'expiry_date' => now()->addDays(7)->format('F j, Y'),
            'renewal_url' => config('app.url') . '/renew',
            'stock_quantity' => '3',
            'threshold' => '5',
            'admin_stock_url' => config('app.url') . '/admin/orders',
        ];

        $variables = [];
        foreach ($emailTemplate->available_variables ?? [] as $var) {
            $variables[$var] = $samples[$var] ?? "{{$var}}";
        }

        return $variables;
    }
}
