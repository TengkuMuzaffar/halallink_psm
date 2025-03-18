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
        'itemID',
        'locationID',
        'userID',
        'paymentID',
        'order_timestamp',
        'deliver_timestamp',
        'order_status',
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
     * Get the item that owns the order.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID');
    }

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
     * Get the checkpoints for the order.
     */
    public function checkpoints()
    {
        return $this->hasMany(Checkpoint::class, 'orderID', 'orderID');
    }
}