<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportValidity extends Model
{
    protected $table = 'report_validities';
    protected $primaryKey = 'reportValidityID';

    protected $fillable = [
        'userID',
        'start_timestamp',
        'end_timestamp',
        'approval',
    ];

    protected $casts = [
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'approval' => 'boolean',
    ];

    /**
     * Get the user associated with the report validity.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    /**
     * Get the reports associated with the report validity.
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'reportValidityID', 'reportValidityID');
    }
}