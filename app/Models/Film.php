<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Film extends Model
{
    protected $fillable = [
        'title',
        'opening_crawl',
        'director',
        'producer',
        'release_date'
    ];

    // Relación: Una película puede tener varios personajes (muchos a muchos)
    public function characters()
    {
        return $this->belongsToMany(Character::class, 'character_film');
    }

    // Relación: Una película puede incluir varios planetas (muchos a muchos)
    public function planets()
    {
        return $this->belongsToMany(Planet::class, 'film_planet');
    }
}
