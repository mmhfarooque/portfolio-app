<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'photo_id',
        'product_type',
        'product_name',
        'product_size',
        'price',
        'shipping',
        'tax',
        'total',
        'currency',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'payment_provider',
        'payment_intent_id',
        'payment_status',
        'paid_at',
        'status',
        'tracking_number',
        'tracking_url',
        'notes',
        'license_type',
        'license_key',
        'license_expires_at',
        'download_count',
        'max_downloads',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'license_expires_at' => 'datetime',
    ];

    /**
     * Generate unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . strtoupper(substr(uniqid(), -8));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Generate license key for digital downloads.
     */
    public static function generateLicenseKey(): string
    {
        return strtoupper(implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(4)),
        ]));
    }

    /**
     * Get the photo for this order.
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order can be downloaded (for digital licenses).
     */
    public function canDownload(): bool
    {
        if (!$this->isPaid()) {
            return false;
        }

        if ($this->product_type !== 'license') {
            return false;
        }

        if ($this->download_count >= $this->max_downloads) {
            return false;
        }

        if ($this->license_expires_at && $this->license_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Increment download count.
     */
    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'indigo',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get payment status color.
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'failed' => 'red',
            'refunded' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scope for paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
