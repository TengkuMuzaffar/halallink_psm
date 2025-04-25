<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verify extends Model
{
    use HasFactory;

    protected $primaryKey = 'verifyID';

    protected $fillable = [
        'verifyID',
        'deliveryID',
        'checkID',
        'verify_status',
        'verify_comment'
    ];

    /**
     * Get the user that owns the verification.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    /**
     * Get the checkpoint that owns the verification.
     */
    public function checkpoint()
    {
        return $this->belongsTo(Checkpoint::class, 'checkID', 'checkID');
    }

    /**
     * Get the vehicle associated with the verification.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicleID', 'vehicleID');
    }

    /**
     * Get the delivery associated with the verification.
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }
    
    /**
     * Scope for verified checkpoints
     */
    public function scopeVerified($query)
    {
        return $query->where('verify_status', 'verified');
    }

    /**
     * Check if verification is completed
     */
    public function isVerified()
    {
        return $this->verify_status === 'verified';
    }
    
    public static function areCheckpointsVerified($orderID, $arrangeNumbers = [1, 2])
    {
        $checkpoints = Checkpoint::where('orderID', $orderID)
            ->whereIn('arrange_number', $arrangeNumbers)
            ->pluck('checkID');
            
        if ($checkpoints->isEmpty()) {
            return false;
        }
        
        $verifyCount = self::whereIn('checkID', $checkpoints)
            ->where('verify_status', 'verified')
            ->count();
            
        return $verifyCount == count($arrangeNumbers);
    }
}