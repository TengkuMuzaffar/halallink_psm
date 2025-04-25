<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $primaryKey = 'deliveryID';
    
    protected $fillable = [
        'start_timestamp',
        'arrive_timestamp',
        'scheduled_date'
    ];
    
    protected $casts = [
        'start_timestamp' => 'datetime',
        'arrive_timestamp' => 'datetime',
        'scheduled_date' => 'date'
    ];
    
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