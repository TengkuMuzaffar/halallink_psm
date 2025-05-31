<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $primaryKey = 'reportID';

    protected $fillable = [
        'companyID',
        'reportValidityID',
    ];

    /**
     * Get the company associated with the report.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'companyID', 'companyID');
    }

    /**
     * Get the report validity associated with the report.
     */
    public function reportValidity()
    {
        return $this->belongsTo(ReportValidity::class, 'reportValidityID', 'reportValidityID');
    }
}