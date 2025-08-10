<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poultry extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'poultryID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'poultry_name',
        'poultry_image',
    ];

    /**
     * Get the items for the poultry.
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'poultryID', 'poultryID');
    }
}