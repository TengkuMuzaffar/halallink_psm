<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SortLocation extends Model
{
    protected $primaryKey = 'sortLocationID';
    
    protected $fillable = [
        'deliveryID',
        'checkID'
    ];
    
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'deliveryID', 'deliveryID');
    }
    
    public function checkpoint()
    {
        return $this->belongsTo(Checkpoint::class, 'checkID', 'checkID');
    }
}