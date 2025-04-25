<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verify extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'verifyID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'userID',
        'checkID',
        'vehicleID',
        'deliveryID',
        'verify_status',
        'verify_comment',
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
     * Check if all verifications for a specific order and arrange numbers are completed
     * 
     * @param int $orderID
     * @param array $arrangeNumbers
     * @return bool
     */
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