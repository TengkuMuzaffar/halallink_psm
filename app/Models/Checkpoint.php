<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkpoint extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'checkID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'orderID',
        'locationID',
        'userID',
        'arrange_number',
        'start_timestamp',
        'finish_timestamp',
        'arrive_timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_timestamp' => 'datetime',
        'finish_timestamp' => 'datetime',
        'arrive_timestamp' => 'datetime',
    ];

    /**
     * Get the order that owns the checkpoint.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    /**
     * Get the location that owns the checkpoint.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'locationID', 'locationID');
    }

    /**
     * Get the user that owns the checkpoint.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    /**
     * Get the verifications for the checkpoint.
     */
    public function verifications()
    {
        return $this->hasMany(Verify::class, 'checkID', 'checkID');
    }
}