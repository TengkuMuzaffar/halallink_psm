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
        'arrive_timestamp',
    ];

    protected $casts = [
        'arrive_timestamp' => 'datetime',
    ];

    // Update relationships
    public function sortLocations()
    {
        return $this->hasMany(SortLocation::class, 'checkID', 'checkID');
    }

   
}