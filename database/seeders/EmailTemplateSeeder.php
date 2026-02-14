<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome Email',
                'description' => 'Sent to new users after registration.',
                'subject' => 'Welcome to {{site_name}}, {{user_name}}!',
                'body_html' => '<h2>Welcome, {{user_name}}!</h2>
<p>Thank you for joining {{site_name}}. We\'re excited to have you on board.</p>
<p>You can access your account anytime by visiting:</p>
<p><a href="{{login_url}}" class="btn">Log In to Your Account</a></p>
<p>If you have any questions, feel free to reach out to us.</p>
<p>Best regards,<br>The {{site_name}} Team</p>',
                'available_variables' => ['user_name', 'login_url', 'site_name'],
                'category' => 'system',
            ],
            [
                'slug' => 'order-confirmation',
                'name' => 'Order Confirmation',
                'description' => 'Sent when a customer places an order.',
                'subject' => 'Order Confirmed: {{order_number}}',
                'body_html' => '<h2>Thank you for your order, {{user_name}}!</h2>
<p>Your order <strong>{{order_number}}</strong> has been confirmed.</p>
<h3>Order Summary</h3>
<p><strong>Total:</strong> {{order_total}}<br>
<strong>Payment Method:</strong> {{payment_method}}</p>
<h3>Items</h3>
{{items_list}}
<p><a href="{{dashboard_url}}" class="btn">View Order Details</a></p>
<p>Thank you for your purchase!</p>',
                'available_variables' => ['user_name', 'order_number', 'order_total', 'payment_method', 'items_list', 'dashboard_url'],
                'category' => 'order',
            ],
            [
                'slug' => 'delivery',
                'name' => 'Delivery Notification',
                'description' => 'Sent when an order is delivered.',
                'subject' => 'Your Order {{order_number}} Has Been Delivered',
                'body_html' => '<h2>Your order has been delivered, {{user_name}}!</h2>
<p>Great news! Your order <strong>{{order_number}}</strong> has been successfully delivered.</p>
<h3>Delivered Items</h3>
{{items_list}}
<p><a href="{{dashboard_url}}" class="btn">View Your Order</a></p>
<p>We hope you enjoy your purchase. If you have any issues, please don\'t hesitate to contact us.</p>',
                'available_variables' => ['user_name', 'order_number', 'items_list', 'dashboard_url'],
                'category' => 'delivery',
            ],
            [
                'slug' => 'credentials-delivered',
                'name' => 'Credentials Delivered',
                'description' => 'Sent when digital credentials/license keys are delivered.',
                'subject' => 'Your Digital Downloads Are Ready - Order {{order_number}}',
                'body_html' => '<h2>Your downloads are ready, {{user_name}}!</h2>
<p>The digital items from order <strong>{{order_number}}</strong> are now available.</p>
<p>You have <strong>{{item_count}}</strong> item(s) ready for download.</p>
<p><a href="{{dashboard_url}}" class="btn">Download Your Items</a></p>
<p>Your download links will remain active according to your license terms.</p>',
                'available_variables' => ['user_name', 'order_number', 'item_count', 'dashboard_url'],
                'category' => 'delivery',
            ],
            [
                'slug' => 'refund',
                'name' => 'Refund Notification',
                'description' => 'Sent when a refund is processed.',
                'subject' => 'Refund Processed for Order {{order_number}}',
                'body_html' => '<h2>Refund Processed</h2>
<p>Hi {{user_name}},</p>
<p>A refund has been processed for your order <strong>{{order_number}}</strong>.</p>
<p><strong>Refund Amount:</strong> {{refund_amount}}<br>
<strong>Payment Method:</strong> {{payment_method}}<br>
<strong>Reason:</strong> {{reason}}</p>
<p>The refund should appear in your account within 5-10 business days.</p>
<p><a href="{{dashboard_url}}" class="btn">View Order Details</a></p>
<p>If you have any questions about this refund, please contact us.</p>',
                'available_variables' => ['user_name', 'order_number', 'refund_amount', 'payment_method', 'reason', 'dashboard_url'],
                'category' => 'order',
            ],
            [
                'slug' => 'subscription-expiring',
                'name' => 'Subscription Expiring',
                'description' => 'Sent when a subscription is about to expire.',
                'subject' => 'Your {{product_name}} Subscription Expires in {{days_remaining}} Days',
                'body_html' => '<h2>Subscription Expiring Soon</h2>
<p>Hi {{user_name}},</p>
<p>Your subscription for <strong>{{product_name}}</strong> will expire on <strong>{{expiry_date}}</strong> (in {{days_remaining}} days).</p>
<p>To continue enjoying uninterrupted access, please renew your subscription.</p>
<p><a href="{{renewal_url}}" class="btn">Renew Now</a></p>
<p>If you choose not to renew, your access will end on the expiry date.</p>',
                'available_variables' => ['user_name', 'product_name', 'days_remaining', 'expiry_date', 'renewal_url'],
                'category' => 'subscription',
            ],
            [
                'slug' => 'low-stock',
                'name' => 'Low Stock Alert',
                'description' => 'Sent to admins when a product variant is running low on stock.',
                'subject' => 'Low Stock Alert: {{product_name}} - {{variant_name}}',
                'body_html' => '<h2>Low Stock Alert</h2>
<p>The following product is running low on stock:</p>
<p><strong>Product:</strong> {{product_name}}<br>
<strong>Variant:</strong> {{variant_name}}<br>
<strong>Current Stock:</strong> {{stock_quantity}}<br>
<strong>Threshold:</strong> {{threshold}}</p>
<p><a href="{{admin_stock_url}}" class="btn">Manage Stock</a></p>
<p>Please restock this item to avoid running out.</p>',
                'available_variables' => ['product_name', 'variant_name', 'stock_quantity', 'threshold', 'admin_stock_url'],
                'category' => 'system',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
