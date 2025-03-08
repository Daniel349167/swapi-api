<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    protected $fillable = [
        'name',
        'classification',
        'designation',
        'average_height',
        'skin_colors',
        'hair_colors',
        'eye_colors',
        'average_lifespan',
        'language'
    ];

    // Relación: Una especie puede tener varios personajes (muchos a muchos)
    public function characters()
    {
        return $this->belongsToMany(Character::class, 'character_species');
    }
}
