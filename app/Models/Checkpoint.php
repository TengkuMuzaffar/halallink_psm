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
        'item_record'
    ];

    protected $casts = [
        'arrange_number' => 'integer',
        'item_record' => 'array'
    ];

    /**
     * Get the delivery associated with the checkpoint.
     * Using the Trip model as an intermediary to find deliveries
     */
    public function delivery()
    {
        // Get delivery through Trip relationship
        return $this->belongsToMany(Delivery::class, 'trips', 'start_checkID', 'deliveryID')
            ->orWhere('trips.end_checkID', $this->checkID);
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