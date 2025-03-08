<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name', 
        'height', 
        'mass', 
        'hair_color', 
        'skin_color',
        'eye_color', 
        'birth_year', 
        'gender', 
        'planet_id'
    ];

    // Relación: Un personaje pertenece a un planeta (homeworld)
    public function planet()
    {
        return $this->belongsTo(Planet::class);
    }

    // Relación: Un personaje puede aparecer en varias películas (muchos a muchos)
    public function films()
    {
        return $this->belongsToMany(Film::class, 'character_film');
    }

    // Relación: Un personaje puede usar varios vehículos (muchos a muchos)
    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'character_vehicle');
    }

    // Relación: Un personaje puede pertenecer a varias especies (muchos a muchos)
    public function species()
    {
        return $this->belongsToMany(Species::class, 'character_species');
    }
}
