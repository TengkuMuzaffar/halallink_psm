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
        'companyID',
        'arrange_number',
        'deliveryID',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'arrange_number' => 'integer',
    ];

    /**
     * Get the delivery associated with the checkpoint.
     */
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }

    /**
     * Get the item associated with the checkpoint.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemID', 'itemID');
    }

    /**
     * Get the location associated with the checkpoint.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'locationID', 'locationID');
    }

    /**
     * Get the company associated with the checkpoint.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'companyID', 'companyID');
    }

    /**
     * Get the order associated with the checkpoint.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    /**
     * Get the tasks for the checkpoint.
     */
    public function task()
    {
        return $this->hasOne(Task::class, 'checkID', 'checkID');
    }

    // /**
    //  * Get the verification for the checkpoint.
    //  */
    // public function verify()
    // {
    //     return $this->hasOne(Verify::class, 'checkID', 'checkID');
    // }

    /**
     * Get the verifications associated with this checkpoint.
     */
    public function verifies()
    {
        return $this->hasMany(Verify::class, 'checkID', 'checkID');
    }
}