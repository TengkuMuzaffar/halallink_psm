<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $primaryKey = 'deliveryID';
    
    protected $fillable = [
        'userID',
        'vehicleID',
        'scheduled_date',
        'start_timestamp',
        'arrive_timestamp'
    ];
    
    protected $casts = [
        'start_timestamp' => 'datetime',
        'arrive_timestamp' => 'datetime',
        'scheduled_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicleID', 'vehicleID');
    }
    
    /**
     * Check if delivery is completed
     */
    public function isCompleted()
    {
        return $this->arrive_timestamp !== null;
    }

    /**
     * Check if delivery is in progress
     */
    public function isInProgress()
    {
        return $this->start_timestamp !== null && $this->arrive_timestamp === null;
    }
    
    /**
     * Get the verifications associated with this delivery.
     */
    public function verifies()
    {
        return $this->hasMany(Verify::class, 'deliveryID', 'deliveryID');
    }
    
    /**
     * Get the checkpoints associated with this delivery.
     * Using the Trip model as an intermediary to find checkpoints
     */
    public function checkpoints()
    {
        return $this->hasManyThrough(
            Checkpoint::class,
            Trip::class,
            'deliveryID', // Foreign key on trips table
            'checkID',    // Foreign key on checkpoints table
            'deliveryID', // Local key on deliveries table
            'start_checkID' // Local key on trips table
        )->union(
            $this->hasManyThrough(
                Checkpoint::class,
                Trip::class,
                'deliveryID', // Foreign key on trips table
                'checkID',    // Foreign key on checkpoints table
                'deliveryID', // Local key on deliveries table
                'end_checkID' // Local key on trips table
            )
        );
    }
}