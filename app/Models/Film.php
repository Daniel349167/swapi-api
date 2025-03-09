<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Film extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id',
        'title',
        'opening_crawl',
        'director',
        'producer',
        'release_date'
    ];
    
    public $incrementing = false;

    public function characters()
    {
        return $this->belongsToMany(Character::class, 'character_film')->withTimestamps();
    }
    
    public function planets()
    {
        return $this->belongsToMany(Planet::class, 'film_planet')->withTimestamps();
    }
}
