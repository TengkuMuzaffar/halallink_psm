<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cert extends Model
{
    protected $table = 'certs';
    protected $primaryKey = 'certID';
    
    protected $fillable = [
        'companyID',
        'cert_type',
        'cert_file'
    ];

    /**
     * Get the company that owns the certification.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'companyID');
    }
}