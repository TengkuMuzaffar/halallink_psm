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
     * Get the vehicle that owns the verification.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicleID', 'vehicleID');
    }
}