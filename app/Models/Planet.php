<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planet extends Model
{
    protected $fillable = [
        'name',
        'rotation_period',
        'orbital_period',
        'diameter',
        'climate',
        'gravity',
        'terrain',
        'surface_water',
        'population'
    ];

    // Relación: Un planeta puede tener varios personajes
    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    // Relación: Un planeta puede estar en varias películas (muchos a muchos)
    public function films()
    {
        return $this->belongsToMany(Film::class, 'film_planet');
    }
}
