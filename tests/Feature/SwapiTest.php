<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Film;
use App\Models\Planet;
use App\Models\SearchLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\Species;
use App\Services\SwapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwapiTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeSwapiService()
    {
        return new class extends SwapiService {
            public function __construct() {}

            public function getPerson($id)
            {
                return [
                    'name'       => 'Luke Skywalker',
                    'height'     => '172',
                    'mass'       => '77',
                    'hair_color' => 'blond',
                    'skin_color' => 'fair',
                    'eye_color'  => 'blue',
                    'birth_year' => '19BBY',
                    'gender'     => 'male',
                    'homeworld'  => 'https://swapi.dev/api/planets/1/',
                    'films'      => ['https://swapi.dev/api/films/1/'],
                ];
            }

            public function getPlanet($id)
            {
                return [
                    'name'            => 'Tatooine',
                    'rotation_period' => '23',
                    'orbital_period'  => '304',
                    'diameter'        => '10465',
                    'climate'         => 'arid',
                    'gravity'         => '1 standard',
                    'terrain'         => 'desert',
                    'surface_water'   => '1',
                    'population'      => '200000',
                    'residents'       => ['https://swapi.dev/api/people/1/'],
                ];
            }

            public function getFilm($id)
            {
                return [
                    'title'         => 'A New Hope',
                    'opening_crawl' => 'It is a period of civil war...',
                    'director'      => 'George Lucas',
                    'producer'      => 'Gary Kurtz, Rick McCallum',
                    'release_date'  => '1977-05-25',
                    'characters'    => ['https://swapi.dev/api/people/1/'],
                    'planets'       => ['https://swapi.dev/api/planets/1/'],
                ];
            }

        };
    }

    protected function authenticateUser()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        return [$user, $token];
    }

    public function test_get_character_from_swapi()
    {
        $this->app->instance(SwapiService::class, $this->fakeSwapiService());
        [$user, $token] = $this->authenticateUser();
        $this->assertNull(Character::find(1));
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/character/1');
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Luke Skywalker']);
        $this->assertNotNull(Character::find(1));
        $this->assertDatabaseHas('planets', ['name' => 'Tatooine']);
        $this->assertDatabaseHas('films', ['title' => 'A New Hope']);
        $this->assertDatabaseHas('search_logs', [
            'user_id'     => $user->id,
            'search_type' => 'character',
            'search_id'   => '1',
        ]);
    }

    public function test_get_planet_from_swapi()
    {
        $this->app->instance(SwapiService::class, $this->fakeSwapiService());
        [$user, $token] = $this->authenticateUser();
        $this->assertNull(Planet::find(1));
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/planet/1');
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Tatooine']);
        $this->assertNotNull(Planet::find(1));
        $this->assertDatabaseHas('search_logs', [
            'user_id'     => $user->id,
            'search_type' => 'planet',
            'search_id'   => 1,
        ]);
    }

    public function test_get_film_from_swapi()
    {
        $this->app->instance(SwapiService::class, $this->fakeSwapiService());
        [$user, $token] = $this->authenticateUser();
        $this->assertNull(Film::find(1));
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/film/1');
        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'A New Hope']);
        $this->assertNotNull(Film::find(1));
        $this->assertDatabaseHas('search_logs', [
            'user_id'     => $user->id,
            'search_type' => 'film',
            'search_id'   => 1,
        ]);
    }

    public function test_get_search_logs_as_admin()
    {
        $adminRole = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $admin->setRelation('role', $adminRole);
        $token = $admin->createToken('admin-token')->plainTextToken;
        SearchLog::factory()->create([
            'user_id'     => $admin->id,
            'search_type' => 'character',
            'search_id'   => 1,
        ]);
        $otherUser = User::factory()->create();
        SearchLog::factory()->create([
            'user_id'     => $otherUser->id,
            'search_type' => 'film',
            'search_id'   => 2,
        ]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/search-logs');
        $response->assertStatus(200);
        $logs = $response->json();
        $this->assertCount(2, $logs);
    }

    public function test_get_search_logs_as_fan()
    {
        $fanRole = Role::create(['name' => 'Fan']);
        $fan = User::factory()->create(['role_id' => $fanRole->id]);
        $fan->setRelation('role', $fanRole);
        $token = $fan->createToken('fan-token')->plainTextToken;
        SearchLog::factory()->create([
            'user_id'     => $fan->id,
            'search_type' => 'character',
            'search_id'   => 1,
        ]);
        SearchLog::factory()->create([
            'user_id'     => $fan->id,
            'search_type' => 'film',
            'search_id'   => 2,
        ]);
        $otherUser = User::factory()->create();
        SearchLog::factory()->create([
            'user_id'     => $otherUser->id,
            'search_type' => 'planet',
            'search_id'   => 3,
        ]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/search-logs');
        $response->assertStatus(200);
        $logs = $response->json();
        $this->assertCount(2, $logs);
    }
}
