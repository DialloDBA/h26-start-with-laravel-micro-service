<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'order_id',
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->payment_number = static::generatePaymentNumber();
        });
    }

    public static function generatePaymentNumber()
    {
        return 'PAY-' . strtoupper(uniqid());
    }
}
