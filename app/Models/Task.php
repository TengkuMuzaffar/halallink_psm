<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $primaryKey = 'taskID';
    
    protected $fillable = [
        'checkID',
        'task_type',
        'task_status',
        'start_timestamp',
        'finish_timestamp'
    ];
    
    protected $casts = [
        'start_timestamp' => 'datetime',
        'finish_timestamp' => 'datetime'
    ];
    
    public function checkpoint()
    {
        return $this->belongsTo(Checkpoint::class, 'checkID', 'checkID');
    }
}