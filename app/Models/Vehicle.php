<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $primaryKey = 'vehicleID';
    
    protected $fillable = [
        'companyID',
        'vehicle_plate',
        'vehicle_load_weight'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class, 'companyID', 'companyID');
    }
}
