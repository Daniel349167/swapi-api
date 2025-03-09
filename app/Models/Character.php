<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id',
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
    
    public $incrementing = false;

    public function planet()
    {
        return $this->belongsTo(Planet::class);
    }
    
    public function films()
    {
        return $this->belongsToMany(Film::class, 'character_film')->withTimestamps();
    }
    
    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'character_vehicle')->withTimestamps();
    }
    
    public function species()
    {
        return $this->belongsToMany(Species::class, 'character_species')->withTimestamps();
    }
}
