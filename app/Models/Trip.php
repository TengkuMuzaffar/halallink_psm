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
        'end_checkID'
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
}