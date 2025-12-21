<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request): Response
    {
        $query = Order::with('photo')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by product type
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->through(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'product_type' => $order->product_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'created_at' => $order->created_at->format('M j, Y g:i A'),
            'photo' => $order->photo ? [
                'id' => $order->photo->id,
                'title' => $order->photo->title,
                'thumbnail_path' => $order->photo->thumbnail_path,
            ] : null,
        ]);

        // Stats
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'revenue' => Order::where('payment_status', 'paid')->sum('total'),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'product_type' => $request->product_type,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order): Response
    {
        $order->load('photo');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'product_type' => $order->product_type,
                'product_options' => $order->product_options,
                'quantity' => $order->quantity,
                'price' => $order->price,
                'total' => $order->total,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'stripe_payment_intent' => $order->stripe_payment_intent,
                'shipping_address' => $order->shipping_address,
                'tracking_number' => $order->tracking_number,
                'tracking_url' => $order->tracking_url,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('M j, Y g:i A'),
                'updated_at' => $order->updated_at->format('M j, Y g:i A'),
                'photo' => $order->photo ? [
                    'id' => $order->photo->id,
                    'title' => $order->photo->title,
                    'slug' => $order->photo->slug,
                    'thumbnail_path' => $order->photo->thumbnail_path,
                    'display_path' => $order->photo->display_path,
                ] : null,
            ],
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $order->update($validated);

        return back()->with('success', 'Order status updated successfully.');
    }

    /**
     * Mark order as shipped.
     */
    public function ship(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255',
            'tracking_url' => 'nullable|url|max:255',
        ]);

        $order->update([
            'status' => 'shipped',
            'tracking_number' => $validated['tracking_number'],
            'tracking_url' => $validated['tracking_url'] ?? null,
        ]);

        // TODO: Send shipping notification email

        return back()->with('success', 'Order marked as shipped.');
    }

    /**
     * Add notes to order.
     */
    public function addNote(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $existingNotes = $order->notes ?? '';
        $newNote = '[' . now()->format('Y-m-d H:i') . '] ' . $validated['notes'];

        $order->update([
            'notes' => $existingNotes ? $existingNotes . "\n\n" . $newNote : $newNote,
        ]);

        return back()->with('success', 'Note added successfully.');
    }
}
