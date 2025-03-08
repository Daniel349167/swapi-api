<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'name',
        'model',
        'manufacturer',
        'cost_in_credits',
        'length',
        'max_atmosphering_speed',
        'crew',
        'passengers',
        'cargo_capacity',
        'consumables',
        'vehicle_class'
    ];

    // Relación: Un vehículo puede ser usado por varios personajes (muchos a muchos)
    public function characters()
    {
        return $this->belongsToMany(Character::class, 'character_vehicle');
    }
}
