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
     */
    public function checkpoints()
    {
        return $this->hasMany(Checkpoint::class, 'deliveryID', 'deliveryID');
    }
}