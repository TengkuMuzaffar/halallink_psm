<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'orderID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'userID',
        'locationID',
        'paymentID',
        'order_timestamp',
        'deliver_timestamp',
        'order_status'
    ];

    // Add this property to specify which fields can be null
    protected $nullable = [
        'locationID',
        'paymentID',
        'order_timestamp',
        'deliver_timestamp',
        'order_status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_timestamp' => 'datetime',
        'deliver_timestamp' => 'datetime',
    ];

    /**
     * Get the location that owns the order.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'locationID', 'locationID');
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    /**
     * Get the payment associated with the order.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'paymentID', 'paymentID');
    }

    /**
     * Get the cart items for the order.
     */
    public function cartItems()
    {
        return $this->hasMany(Cart::class, 'orderID', 'orderID');
    }

    /**
     * Get the items through cart.
     */
    public function items()
    {
        return $this->hasManyThrough(
            Item::class,
            Cart::class,
            'orderID', // Foreign key on carts table
            'itemID',   // Foreign key on items table
            'orderID',  // Local key on orders table
            'itemID'    // Local key on carts table
        );
    }

    /**
     * Get the checkpoints for the order.
     */
    public function checkpoints()
    {
        return $this->hasMany(Checkpoint::class, 'orderID', 'orderID');
    }
}