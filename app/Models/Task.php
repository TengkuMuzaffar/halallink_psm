<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;
    
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