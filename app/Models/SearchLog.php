<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $fillable = [
        'user_id', 'search_type', 'search_id'
    ];

    /**
     * Relación: cada log pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
