<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $primaryKey = 'deliveryID';

    protected $fillable = [
        'userID',
        'vehicleID',
        'scheduled_date',
        'start_timestamp',
        'end_timestamp'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user associated with the delivery.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    /**
     * Get the vehicle associated with the delivery.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicleID', 'vehicleID');
    }

    /**
     * Get the trips associated with the delivery.
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
        return $this->hasMany(Trip::class, 'deliveryID', 'deliveryID');
    }
    
    public function trips()
    {
        return $this->belongsTo(Trip::class, 'deliveryID', 'deliveryID');
    }

}