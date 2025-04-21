<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $primaryKey = 'locationID';
    
    protected $fillable = [
        'companyID',
        'company_address',
        'location_type'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class, 'companyID', 'companyID');
    }
    
    /**
     * Get the items associated with this location.
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'locationID', 'locationID');
    }
    
    /**
     * Get the items where this location is the slaughterhouse.
     */
    public function slaughterhouseItems()
    {
        return $this->hasMany(Item::class, 'slaughterhouse_locationID', 'locationID');
    }
    
    /**
     * Get the checkpoints associated with this location.
     */
    public function checkpoints()
    {
        return $this->hasMany(Checkpoint::class, 'locationID', 'locationID');
    }
}
