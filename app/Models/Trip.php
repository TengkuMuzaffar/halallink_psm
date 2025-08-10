<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $primaryKey = 'tripID';

    protected $fillable = [
        'deliveryID',
        'start_checkID',
        'end_checkID',
        'orderID'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the delivery associated with the trip.
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }

    /**
     * Get the order associated with the trip.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    /**
     * Get the starting checkpoint for this trip.
     */
    public function startCheckpoint()
    {
        return $this->belongsTo(Checkpoint::class, 'start_checkID', 'checkID');
    }

    /**
     * Get the ending checkpoint for this trip.
     */
    public function endCheckpoint()
    {
        return $this->belongsTo(Checkpoint::class, 'end_checkID', 'checkID');
    }
    
    /**
     * Get verifications associated with this trip's delivery.
     */
    public function verifications()
    {
        return $this->hasMany(Verify::class, 'deliveryID', 'deliveryID');
    }

    /**
     * Get the starting location for this trip through the checkpoint.
     */
    public function startLocation()
    {
        return $this->hasOneThrough(
            Location::class,
            Checkpoint::class,
            'checkID',     // Foreign key on checkpoints table
            'locationID',  // Foreign key on locations table
            'start_checkID', // Local key on trips table
            'locationID'   // Local key on checkpoints table
        );
    }

    /**
     * Get the ending location for this trip through the checkpoint.
     */
    public function endLocation()
    {
        return $this->hasOneThrough(
            Location::class,
            Checkpoint::class,
            'checkID',     // Foreign key on checkpoints table
            'locationID',  // Foreign key on locations table
            'end_checkID', // Local key on trips table
            'locationID'   // Local key on checkpoints table
        );
    }
}