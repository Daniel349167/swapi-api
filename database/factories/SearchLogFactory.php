<?php

namespace Database\Factories;

use App\Models\SearchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchLogFactory extends Factory
{
    protected $model = SearchLog::class;

    public function definition()
    {
        return [
            'search_type' => 'character',
            'search_id'   => '1',
            'user_id'     => 1,
        ];
    }
}
