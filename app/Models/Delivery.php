<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $primaryKey = 'deliveryID';
    
    protected $fillable = [
        'verifyID',
        'start_timestamp',
        'arrive_timestamp'
    ];
    
    protected $casts = [
        'start_timestamp' => 'datetime',
        'arrive_timestamp' => 'datetime'
    ];
    
    public function verify()
    {
        return $this->belongsTo(Verify::class, 'verifyID', 'verifyID');
    }
    
    public function sortLocations()
    {
        return $this->hasMany(SortLocation::class, 'deliveryID', 'deliveryID');
    }
}