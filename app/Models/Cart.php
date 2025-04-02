<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'cartID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'itemID',
        'orderID',
        'quantity',
        'price_at_purchase',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_at_purchase' => 'decimal:2',
    ];

    /**
     * Get the item associated with the cart.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID');
    }

    /**
     * Get the order associated with the cart.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }
}