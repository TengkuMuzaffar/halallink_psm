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
}
