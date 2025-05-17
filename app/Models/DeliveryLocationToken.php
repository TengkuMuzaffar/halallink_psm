<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLocationToken extends Model
{
    use HasFactory;

    protected $table = 'delivery_location_token'; // Specify the table name

    protected $fillable = [
        'deliveryID',
        'locationID',
        'token',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * Get the delivery associated with the token.
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }

    /**
     * Get the location associated with the token.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'locationID', 'locationID');
    }
}