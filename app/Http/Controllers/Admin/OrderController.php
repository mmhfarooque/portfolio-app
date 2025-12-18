<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
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

        $orders = $query->paginate(20);

        // Stats
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'revenue' => Order::where('payment_status', 'paid')->sum('total'),
        ];

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order)
    {
        $order->load('photo');

        return view('admin.orders.show', compact('order'));
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
