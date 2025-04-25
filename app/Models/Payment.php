<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'paymentID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_amount',
        'payment_status',
        'payment_reference',
        'bill_code',
        'transaction_id',
        'payment_timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_timestamp' => 'datetime',
    ];

    /**
     * Get the order associated with the payment.
     */
    public function order()
    {
        return $this->hasOne(Order::class, 'paymentID', 'paymentID');
    }
}