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

    /**
     * Obtiene información de una persona (personaje) desde SWAPI.
     *
     * @param int $id
     * @return array
     */
    public function getPerson($id)
    {
        $response = Http::get($this->baseUrl . "people/{$id}/");
        return $response->json();
    }

    /**
     * Obtiene información de un planeta desde SWAPI.
     *
     * @param int $id
     * @return array
     */
    public function getPlanet($id)
    {
        $response = Http::get($this->baseUrl . "planets/{$id}/");
        return $response->json();
    }

    /**
     * Obtiene información de una película desde SWAPI.
     *
     * @param int $id
     * @return array
     */
    public function getFilm($id)
    {
        $response = Http::get($this->baseUrl . "films/{$id}/");
        return $response->json();
    }

    /**
     * Obtiene información de un vehículo desde SWAPI.
     *
     * @param int $id
     * @return array
     */
    public function getVehicle($id)
    {
        $response = Http::get($this->baseUrl . "vehicles/{$id}/");
        return $response->json();
    }

    /**
     * Obtiene información de una especie desde SWAPI.
     *
     * @param int $id
     * @return array
     */
    public function getSpecies($id)
    {
        $response = Http::get($this->baseUrl . "species/{$id}/");
        return $response->json();
    }
}
