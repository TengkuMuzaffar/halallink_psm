<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $primaryKey = 'companyID';

    protected $fillable = [
        'formID',
        'company_name',
        'company_image',
        'company_type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (!$company->formID) {
                $company->formID = 'company' . Str::random(10);
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'companyID');
    }

    public function admin()
    {
        return $this->hasOne(User::class, 'companyID')->where('role', 'admin');
    }
    
    public function locations()
    {
        return $this->hasMany(Location::class, 'companyID', 'companyID');
    }

    /**
     * Get the reports associated with the company.
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'companyID', 'companyID');
    }
}