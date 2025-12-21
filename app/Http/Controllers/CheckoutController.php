<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Photo;
use App\Services\PaymentService;
use App\Services\PrintService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected PrintService $printService
    ) {}

    /**
     * Show checkout page for a product.
     */
    public function show(Request $request, Photo $photo): Response
    {
        if ($photo->status !== 'published') {
            abort(404);
        }

        $productId = $request->get('product');
        $products = $this->printService->getProducts();

        // Find the selected product
        $selectedProduct = null;
        foreach ($products as $product) {
            $id = is_array($product) ? ($product['id'] ?? null) : ($product->id ?? null);
            if ($id === $productId) {
                $selectedProduct = $product;
                break;
            }
        }

        if (!$selectedProduct) {
            return Inertia::location(route('print.options', $photo->slug));
        }

        $isPaymentConfigured = $this->paymentService->isConfigured();
        $stripePublicKey = $this->paymentService->getPublicKey();

        // Calculate costs
        $price = is_array($selectedProduct) ? ($selectedProduct['price'] ?? 0) : ($selectedProduct->price ?? 0);
        $productName = is_array($selectedProduct) ? ($selectedProduct['name'] ?? 'Product') : ($selectedProduct->name ?? 'Product');
        $productType = $this->getProductType($productId);

        $shipping = $this->paymentService->calculateShipping($productType);
        $subtotal = $price + $shipping;
        $tax = $this->paymentService->calculateTax($subtotal);
        $total = $subtotal + $tax;

        return Inertia::render('Public/Checkout/Show', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
            ],
            'selectedProduct' => $selectedProduct,
            'productId' => $productId,
            'productName' => $productName,
            'productType' => $productType,
            'price' => $price,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'isPaymentConfigured' => $isPaymentConfigured,
            'stripePublicKey' => $stripePublicKey,
        ]);
    }

    /**
     * Process checkout and create order.
     */
    public function process(Request $request, Photo $photo)
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
            'product_name' => 'required|string|max:255',
            'product_type' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'shipping' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'shipping_address_line1' => 'required_unless:product_type,license|nullable|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required_unless:product_type,license|nullable|string|max:255',
            'shipping_state' => 'required_unless:product_type,license|nullable|string|max:100',
            'shipping_postal_code' => 'required_unless:product_type,license|nullable|string|max:20',
            'shipping_country' => 'required_unless:product_type,license|nullable|string|max:2',
        ]);

        // Get license details if applicable
        $licenseType = null;
        $maxDownloads = 5;

        if ($validated['product_type'] === 'license') {
            $products = $this->printService->getProducts();
            foreach ($products as $product) {
                $id = is_array($product) ? ($product['id'] ?? null) : ($product->id ?? null);
                if ($id === $validated['product_id']) {
                    $licenseType = is_array($product) ? ($product['license_type'] ?? 'personal') : ($product->license_type ?? 'personal');
                    break;
                }
            }

            // Set max downloads based on license type
            $maxDownloads = match($licenseType) {
                'personal' => 5,
                'commercial' => 10,
                'extended' => 50,
                default => 5,
            };
        }

        // Create order
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'photo_id' => $photo->id,
            'product_type' => $validated['product_type'],
            'product_name' => $validated['product_name'],
            'product_size' => $validated['product_id'],
            'price' => $validated['price'],
            'shipping' => $validated['shipping'],
            'tax' => $validated['tax'],
            'total' => $validated['total'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'shipping_address_line1' => $validated['shipping_address_line1'] ?? null,
            'shipping_address_line2' => $validated['shipping_address_line2'] ?? null,
            'shipping_city' => $validated['shipping_city'] ?? null,
            'shipping_state' => $validated['shipping_state'] ?? null,
            'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
            'shipping_country' => $validated['shipping_country'] ?? null,
            'payment_provider' => 'stripe',
            'license_type' => $licenseType,
            'max_downloads' => $maxDownloads,
        ]);

        // Create Stripe Payment Intent
        $paymentIntent = $this->paymentService->createPaymentIntent($order);

        if (!$paymentIntent) {
            $order->delete();
            return back()->with('error', 'Unable to process payment. Please try again.');
        }

        LoggingService::activity('order.created', "Order {$order->order_number} created for photo {$photo->id}");

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'client_secret' => $paymentIntent['client_secret'],
        ]);
    }

    /**
     * Handle payment confirmation.
     */
    public function confirm(Request $request, Order $order): Response
    {
        // Verify payment status with Stripe
        if ($order->payment_intent_id) {
            $paymentIntent = $this->paymentService->retrievePaymentIntent($order->payment_intent_id);

            if ($paymentIntent && $paymentIntent['status'] === 'succeeded') {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'confirmed',
                ]);

                // Generate license key for digital products
                if ($order->product_type === 'license' && !$order->license_key) {
                    $order->update([
                        'license_key' => Order::generateLicenseKey(),
                        'license_expires_at' => now()->addYear(),
                    ]);
                }
            }
        }

        $order->load('photo');

        return Inertia::render('Public/Checkout/Confirmation', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'product_name' => $order->product_name,
                'product_type' => $order->product_type,
                'total' => $order->total,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'license_key' => $order->license_key,
                'photo' => $order->photo ? [
                    'title' => $order->photo->title,
                    'slug' => $order->photo->slug,
                    'thumbnail_path' => $order->photo->thumbnail_path,
                ] : null,
            ],
        ]);
    }

    /**
     * Handle Stripe webhook.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        $result = $this->paymentService->handleWebhook($payload, $signature);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    /**
     * Download licensed photo.
     */
    public function download(Request $request, Order $order)
    {
        $licenseKey = $request->get('key');

        // Verify license key
        if ($order->license_key !== $licenseKey) {
            abort(403, 'Invalid license key.');
        }

        // Check if download is allowed
        if (!$order->canDownload()) {
            if (!$order->isPaid()) {
                abort(403, 'Payment not completed.');
            }
            if ($order->product_type !== 'license') {
                abort(403, 'This is not a digital product.');
            }
            if ($order->download_count >= $order->max_downloads) {
                abort(403, 'Download limit reached.');
            }
            if ($order->license_expires_at && $order->license_expires_at->isPast()) {
                abort(403, 'License has expired.');
            }
        }

        $photo = $order->photo;

        if (!$photo || !$photo->original_path) {
            abort(404, 'Photo not found.');
        }

        // Increment download count
        $order->incrementDownloads();

        LoggingService::activity('download.licensed', "Licensed download for order {$order->order_number}");

        // Return the high-resolution original file
        $filePath = storage_path('app/public/' . $photo->original_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        $filename = $photo->slug . '_licensed.' . pathinfo($filePath, PATHINFO_EXTENSION);

        return response()->download($filePath, $filename);
    }

    /**
     * Get product type from product ID.
     */
    protected function getProductType(string $productId): string
    {
        if (str_starts_with($productId, 'print_')) {
            return 'print';
        } elseif (str_starts_with($productId, 'canvas_')) {
            return 'canvas';
        } elseif (str_starts_with($productId, 'metal_')) {
            return 'metal';
        } elseif (str_starts_with($productId, 'license_')) {
            return 'license';
        }

        return 'print';
    }
}
