<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verify extends Model
{
    use HasFactory;

    protected $primaryKey = 'verifyID';

    protected $fillable = [
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
     * Get the delivery associated with the verification.
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }
    
    /**
     * Get the checkpoint associated with the verification.
     */
    public function checkpoint()
    {
        return $this->belongsTo(Checkpoint::class, 'checkID', 'checkID');
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
}