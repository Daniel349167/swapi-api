<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Planet;
use App\Models\Film;
use App\Models\SearchLog;
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
     * @OA\Get(
     *     path="/api/character/{id}",
     *     summary="Obtener y almacenar un personaje",
     *     tags={"SWAPI"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del personaje en SWAPI",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Personaje almacenado y retornado correctamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Personaje no encontrado")
     * )
     */
    public function getCharacter(Request $request, $id)
    {
        $character = Character::with(['planet', 'films', 'vehicles', 'species'])->find($id);
        if ($character) {
            SearchLog::create([
                'user_id'     => $request->user()->id,
                'search_type' => 'character',
                'search_id'   => $id,
            ]);
            return response()->json($character);
        }
        $data = $this->swapi->getPerson($id);
        if (!isset($data['name'])) {
            return response()->json(['error' => 'Personaje no encontrado'], 404);
        }
        DB::beginTransaction();
        try {
            $planetId = null;
            if (isset($data['homeworld']) && $data['homeworld']) {
                $planetUrl = $data['homeworld'];
                $planetUrlParts = explode('/', trim($planetUrl, '/'));
                $planetSwapiId = end($planetUrlParts);
                $planetData = $this->swapi->getPlanet($planetSwapiId);
                $planet = Planet::firstOrCreate(
                    ['id' => $planetSwapiId],
                    [
                        'name'            => $planetData['name'],
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
                'id'         => $id,
                'name'       => $data['name'],
                'height'     => $data['height'],
                'mass'       => $data['mass'],
                'hair_color' => $data['hair_color'],
                'skin_color' => $data['skin_color'],
                'eye_color'  => $data['eye_color'],
                'birth_year' => $data['birth_year'],
                'gender'     => $data['gender'],
                'planet_id'  => $planetId,
            ]);
            if (isset($data['films']) && is_array($data['films'])) {
                foreach ($data['films'] as $filmUrl) {
                    $parts = explode('/', trim($filmUrl, '/'));
                    $filmSwapiId = end($parts);
                    $filmData = $this->swapi->getFilm($filmSwapiId);
                    $film = Film::firstOrCreate(
                        ['id' => $filmSwapiId],
                        [
                            'title'          => $filmData['title'],
                            'opening_crawl'  => $filmData['opening_crawl'] ?? null,
                            'director'       => $filmData['director'] ?? null,
                            'producer'       => $filmData['producer'] ?? null,
                            'release_date'   => $filmData['release_date'] ?? null,
                        ]
                    );
                    $character->films()->syncWithoutDetaching([$film->id]);
                }
            }
            DB::commit();
            SearchLog::create([
                'user_id'     => $request->user()->id,
                'search_type' => 'character',
                'search_id'   => $id,
            ]);
            return response()->json($character->load(['planet', 'films']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/planet/{id}",
     *     summary="Obtener y almacenar un planeta",
     *     tags={"SWAPI"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del planeta en SWAPI",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Planeta almacenado y retornado correctamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Planeta no encontrado")
     * )
     */
    public function getPlanet(Request $request, $id)
    {
        $planet = Planet::with('characters')->find($id);
        if ($planet) {
            SearchLog::create([
                'user_id'     => $request->user()->id,
                'search_type' => 'planet',
                'search_id'   => $id,
            ]);
            return response()->json($planet);
        }
        $data = $this->swapi->getPlanet($id);
        if (!isset($data['name'])) {
            return response()->json(['error' => 'Planeta no encontrado'], 404);
        }
        DB::beginTransaction();
        try {
            $planet = Planet::create([
                'id'              => $id,
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
            SearchLog::create([
                'user_id'     => $request->user()->id,
                'search_type' => 'planet',
                'search_id'   => $id,
            ]);
            return response()->json($planet->load('characters'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/film/{id}",
     *     summary="Obtener y almacenar una película",
     *     tags={"SWAPI"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la película en SWAPI",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Película almacenada y retornada correctamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Película no encontrada")
     * )
     */
    public function getFilm(Request $request, $id)
    {
        $film = Film::with(['characters', 'planets'])->find($id);
        if ($film) {
            SearchLog::create([
                'user_id'     => $request->user()->id,
                'search_type' => 'film',
                'search_id'   => $id,
            ]);
            return response()->json($film);
        }
        $data = $this->swapi->getFilm($id);
        if (!isset($data['title'])) {
            return response()->json(['error' => 'Película no encontrada'], 404);
        }
        DB::beginTransaction();
        try {
            $film = Film::create([
                'id'             => $id,
                'title'          => $data['title'],
                'opening_crawl'  => $data['opening_crawl'] ?? null,
                'director'       => $data['director'] ?? null,
                'producer'       => $data['producer'] ?? null,
                'release_date'   => $data['release_date'] ?? null,
            ]);
            if (isset($data['characters']) && is_array($data['characters'])) {
                foreach ($data['characters'] as $characterUrl) {
                    $parts = explode('/', trim($characterUrl, '/'));
                    $characterSwapiId = end($parts);
                    $character = Character::find($characterSwapiId);
                    if (!$character) {
                        $characterData = $this->swapi->getPerson($characterSwapiId);
                        if (isset($characterData['name'])) {
                            $planetId = null;
                            if (isset($characterData['homeworld']) && $characterData['homeworld']) {
                                $planetUrlParts = explode('/', trim($characterData['homeworld'], '/'));
                                $planetSwapiId = end($planetUrlParts);
                                $planetData = $this->swapi->getPlanet($planetSwapiId);
                                $planet = Planet::firstOrCreate(
                                    ['id' => $planetSwapiId],
                                    [
                                        'name'            => $planetData['name'],
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
            SearchLog::create([
                'user_id'     => $request->user()->id,
                'search_type' => 'film',
                'search_id'   => $id,
            ]);
            return response()->json($film->load(['characters', 'planets']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
