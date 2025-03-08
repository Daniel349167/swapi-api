<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Planet;
use App\Models\Film;
use App\Services\SwapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwapiController extends Controller
{
    protected $swapi;

    public function __construct(SwapiService $swapi)
    {
        $this->swapi = $swapi;
    }

    /**
     * Obtiene un personaje. Si ya existe en la base de datos, se retorna;
     * de lo contrario se consulta SWAPI, se almacena y se retorna.
     */
    public function getCharacter(Request $request, $id)
    {
        // Revisar si existe en base de datos
        $character = Character::with(['planet', 'films', 'vehicles', 'species'])->find($id);
        if ($character) {
            return response()->json($character);
        }

        // Consultar SWAPI
        $data = $this->swapi->getPerson($id);
        if (!isset($data['name'])) {
            return response()->json(['error' => 'Personaje no encontrado'], 404);
        }

        DB::beginTransaction();
        try {
            // Procesar planeta (homeworld)
            $planetId = null;
            if (isset($data['homeworld']) && $data['homeworld']) {
                $planetUrl = $data['homeworld'];
                $planetUrlParts = explode('/', trim($planetUrl, '/'));
                $planetSwapiId = end($planetUrlParts);

                $planetData = $this->swapi->getPlanet($planetSwapiId);
                $planet = Planet::firstOrCreate(
                    ['name' => $planetData['name']],
                    [
                        'rotation_period' => $planetData['rotation_period'] ?? null,
                        'orbital_period' => $planetData['orbital_period'] ?? null,
                        'diameter' => $planetData['diameter'] ?? null,
                        'climate' => $planetData['climate'] ?? null,
                        'gravity' => $planetData['gravity'] ?? null,
                        'terrain' => $planetData['terrain'] ?? null,
                        'surface_water' => $planetData['surface_water'] ?? null,
                        'population' => $planetData['population'] ?? null,
                    ]
                );
                $planetId = $planet->id;
            }

            // Crear el personaje
            $character = Character::create([
                'id'          => $id, // Usar el id de SWAPI
                'name'        => $data['name'],
                'height'      => $data['height'],
                'mass'        => $data['mass'],
                'hair_color'  => $data['hair_color'],
                'skin_color'  => $data['skin_color'],
                'eye_color'   => $data['eye_color'],
                'birth_year'  => $data['birth_year'],
                'gender'      => $data['gender'],
                'planet_id'   => $planetId,
            ]);

            // Relacionar películas
            if (isset($data['films']) && is_array($data['films'])) {
                foreach ($data['films'] as $filmUrl) {
                    $parts = explode('/', trim($filmUrl, '/'));
                    $filmSwapiId = end($parts);
                    $filmData = $this->swapi->getFilm($filmSwapiId);

                    $film = Film::firstOrCreate(
                        ['title' => $filmData['title']],
                        [
                            'opening_crawl' => $filmData['opening_crawl'] ?? null,
                            'director'      => $filmData['director'] ?? null,
                            'producer'      => $filmData['producer'] ?? null,
                            'release_date'  => $filmData['release_date'] ?? null,
                        ]
                    );
                    $character->films()->syncWithoutDetaching([$film->id]);
                }
            }

            DB::commit();
            return response()->json($character->load(['planet', 'films']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene un planeta. Si ya existe en la base de datos, se retorna;
     * de lo contrario se consulta SWAPI, se almacena y se relaciona con los personajes (residentes).
     */
    public function getPlanet(Request $request, $id)
    {
        // Revisar si el planeta ya existe en DB
        $planet = Planet::with('characters')->where('id', $id)->first();
        if ($planet) {
            return response()->json($planet);
        }

        $data = $this->swapi->getPlanet($id);
        if (!isset($data['name'])) {
            return response()->json(['error' => 'Planeta no encontrado'], 404);
        }

        DB::beginTransaction();
        try {
            $planet = Planet::create([
                'id'              => $id, // Usar el id de SWAPI
                'name'            => $data['name'],
                'rotation_period' => $data['rotation_period'] ?? null,
                'orbital_period'  => $data['orbital_period'] ?? null,
                'diameter'        => $data['diameter'] ?? null,
                'climate'         => $data['climate'] ?? null,
                'gravity'         => $data['gravity'] ?? null,
                'terrain'         => $data['terrain'] ?? null,
                'surface_water'   => $data['surface_water'] ?? null,
                'population'      => $data['population'] ?? null,
            ]);

            // Relacionar residentes
            if (isset($data['residents']) && is_array($data['residents'])) {
                foreach ($data['residents'] as $residentUrl) {
                    $parts = explode('/', trim($residentUrl, '/'));
                    $characterSwapiId = end($parts);
                    $character = Character::find($characterSwapiId);
                    if (!$character) {
                        $characterData = $this->swapi->getPerson($characterSwapiId);
                        if (isset($characterData['name'])) {
                            $character = Character::create([
                                'id'         => $characterSwapiId,
                                'name'       => $characterData['name'],
                                'height'     => $characterData['height'],
                                'mass'       => $characterData['mass'],
                                'hair_color' => $characterData['hair_color'],
                                'skin_color' => $characterData['skin_color'],
                                'eye_color'  => $characterData['eye_color'],
                                'birth_year' => $characterData['birth_year'],
                                'gender'     => $characterData['gender'],
                                'planet_id'  => $id,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json($planet->load('characters'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene una película. Si ya existe en la base de datos, se retorna;
     * de lo contrario se consulta SWAPI, se almacena y se relaciona con personajes y planetas.
     */
    public function getFilm(Request $request, $id)
    {
        // Revisar si la película ya existe en DB
        $film = Film::with(['characters', 'planets'])->where('id', $id)->first();
        if ($film) {
            return response()->json($film);
        }

        $data = $this->swapi->getFilm($id);
        if (!isset($data['title'])) {
            return response()->json(['error' => 'Película no encontrada'], 404);
        }

        DB::beginTransaction();
        try {
            $film = Film::create([
                'id'             => $id, // Usar el id de SWAPI
                'title'          => $data['title'],
                'opening_crawl'  => $data['opening_crawl'] ?? null,
                'director'       => $data['director'] ?? null,
                'producer'       => $data['producer'] ?? null,
                'release_date'   => $data['release_date'] ?? null,
            ]);

            // Relacionar personajes
            if (isset($data['characters']) && is_array($data['characters'])) {
                foreach ($data['characters'] as $characterUrl) {
                    $parts = explode('/', trim($characterUrl, '/'));
                    $characterSwapiId = end($parts);
                    $character = Character::find($characterSwapiId);
                    if (!$character) {
                        $characterData = $this->swapi->getPerson($characterSwapiId);
                        if (isset($characterData['name'])) {
                            // Procesar homeworld del personaje
                            $planetId = null;
                            if (isset($characterData['homeworld']) && $characterData['homeworld']) {
                                $planetUrl = $characterData['homeworld'];
                                $planetUrlParts = explode('/', trim($planetUrl, '/'));
                                $planetSwapiId = end($planetUrlParts);
                                $planetData = $this->swapi->getPlanet($planetSwapiId);
                                $planet = Planet::firstOrCreate(
                                    ['name' => $planetData['name']],
                                    [
                                        'rotation_period' => $planetData['rotation_period'] ?? null,
                                        'orbital_period'  => $planetData['orbital_period'] ?? null,
                                        'diameter'        => $planetData['diameter'] ?? null,
                                        'climate'         => $planetData['climate'] ?? null,
                                        'gravity'         => $planetData['gravity'] ?? null,
                                        'terrain'         => $planetData['terrain'] ?? null,
                                        'surface_water'   => $planetData['surface_water'] ?? null,
                                        'population'      => $planetData['population'] ?? null,
                                    ]
                                );
                                $planetId = $planet->id;
                            }

                            $character = Character::create([
                                'id'         => $characterSwapiId,
                                'name'       => $characterData['name'],
                                'height'     => $characterData['height'],
                                'mass'       => $characterData['mass'],
                                'hair_color' => $characterData['hair_color'],
                                'skin_color' => $characterData['skin_color'],
                                'eye_color'  => $characterData['eye_color'],
                                'birth_year' => $characterData['birth_year'],
                                'gender'     => $characterData['gender'],
                                'planet_id'  => $planetId,
                            ]);
                        }
                    }
                    $film->characters()->syncWithoutDetaching([$character->id]);
                }
            }

            // Relacionar planetas
            if (isset($data['planets']) && is_array($data['planets'])) {
                foreach ($data['planets'] as $planetUrl) {
                    $parts = explode('/', trim($planetUrl, '/'));
                    $planetSwapiId = end($parts);
                    $planet = Planet::find($planetSwapiId);
                    if (!$planet) {
                        $planetData = $this->swapi->getPlanet($planetSwapiId);
                        if (isset($planetData['name'])) {
                            $planet = Planet::create([
                                'id'              => $planetSwapiId,
                                'name'            => $planetData['name'],
                                'rotation_period' => $planetData['rotation_period'] ?? null,
                                'orbital_period'  => $planetData['orbital_period'] ?? null,
                                'diameter'        => $planetData['diameter'] ?? null,
                                'climate'         => $planetData['climate'] ?? null,
                                'gravity'         => $planetData['gravity'] ?? null,
                                'terrain'         => $planetData['terrain'] ?? null,
                                'surface_water'   => $planetData['surface_water'] ?? null,
                                'population'      => $planetData['population'] ?? null,
                            ]);
                        }
                    }
                    $film->planets()->syncWithoutDetaching([$planet->id]);
                }
            }

            DB::commit();
            return response()->json($film->load(['characters', 'planets']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
