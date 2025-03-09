<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SwapiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://swapi.dev/api/';
    }

    public function getPerson($id)
    {
        $response = Http::get($this->baseUrl . "people/{$id}/");
        return $response->json();
    }

    public function getPlanet($id)
    {
        $response = Http::get($this->baseUrl . "planets/{$id}/");
        return $response->json();
    }

    public function getFilm($id)
    {
        $response = Http::get($this->baseUrl . "films/{$id}/");
        return $response->json();
    }

    public function getVehicle($id)
    {
        $response = Http::get($this->baseUrl . "vehicles/{$id}/");
        return $response->json();
    }

    public function getSpecies($id)
    {
        $response = Http::get($this->baseUrl . "species/{$id}/");
        return $response->json();
    }
}
