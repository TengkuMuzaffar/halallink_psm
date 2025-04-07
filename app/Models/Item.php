<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'itemID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'poultryID',
        'userID',
        'locationID',
        'measurement_type',
        'item_image',
        'measurement_value',
        'price',
        'stock',
    ];

    /**
     * Get the poultry that owns the item.
     */
    public function poultry()
    {
        return $this->belongsTo(Poultry::class, 'poultryID', 'poultryID');
    }

    /**
     * Get the user that owns the item.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID', 'userID');
    }

    /**
     * Get the location that owns the item.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'locationID', 'locationID');
    }

    /**
     * Get the orders for the item.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'itemID', 'itemID');
    }

    /**
     * Decrease the item stock
     *
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock($quantity)
    {
        if ($this->stock >= $quantity) {
            $this->stock -= $quantity;
            return $this->save();
        }
        return false;
    }
}