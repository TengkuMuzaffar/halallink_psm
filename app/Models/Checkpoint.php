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
        'deliveryID'
    ];

    protected $casts = [];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }

    // Update relationships
    public function sortLocations()
    {
        return $this->hasMany(SortLocation::class, 'checkID', 'checkID');
    }

   
}