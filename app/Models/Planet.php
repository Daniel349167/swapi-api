<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planet extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id',
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
    
    public $incrementing = false;

    public function characters()
    {
        return $this->hasMany(Character::class);
    }
    
    public function films()
    {
        return $this->belongsToMany(Film::class, 'film_planet')->withTimestamps();
    }
}
