<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;

class Order extends Model
{
    use HasFactory;

    protected static ?NumberFormatter $currencyFormatter = null;

    protected $fillable = [
        'order_number',
        'user_id',
        'total_amount',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'grand_total',
        'currency',
        'shipping_address',
        'billing_address',
        'payment_method',
        'status',
    ];


    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        "payment_method" => PaymentMethod::class,
        "status" => OrderStatus::class,
    ];


    public static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_number = static::generateOrderNumber();
        });
    }

    public static function generateOrderNumber()
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    public function calculateGrandTotal()
    {
        $this->grand_total = $this->total_amount + $this->tax_amount + $this->shipping_amount - $this->discount_amount;

        return $this->grand_total;
    }

    protected static function currencyFormatter(): NumberFormatter
    {
        return static::$currencyFormatter ??= NumberFormatter::create('en_US', NumberFormatter::CURRENCY);
    }

    public function getGrandTotalAttribute($value)
    {
        return static::currencyFormatter()->formatCurrency($value, $this->currency);
    }
}
