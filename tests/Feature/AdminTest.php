<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser()
    {
        $adminRole = Role::create(['name' => 'Admin']);

        $admin = User::factory()->create([
            'password' => bcrypt('admin123'),
            'role_id'  => $adminRole->id,
        ]);
        $admin->setRelation('role', $adminRole);

        $token = $admin->createToken('admin-token')->plainTextToken;
        return [$admin, $token];
    }

    public function test_admin_can_assign_role()
    {
        [$admin, $token] = $this->createAdminUser();
        $user = User::factory()->create();
        $newRole = Role::create(['name' => 'Moderator']);

        $payload = [
            'user_id' => $user->id,
            'role_id' => $newRole->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/assign-role', $payload);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Rol asignado correctamente.']);

        $this->assertDatabaseHas('users', [
            'id'      => $user->id,
            'role_id' => $newRole->id,
        ]);
    }

    public function test_admin_can_revoke_role()
    {
        [$admin, $token] = $this->createAdminUser();
        $moderatorRole = Role::create(['name' => 'Moderator']);
        $user = User::factory()->create([
            'role_id' => $moderatorRole->id,
        ]);
        $user->setRelation('role', $moderatorRole);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/revoke-role/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Rol revocado correctamente.']);

        $this->assertDatabaseHas('users', [
            'id'      => $user->id,
            'role_id' => null,
        ]);
    }

    public function test_admin_can_create_user()
    {
        [$admin, $token] = $this->createAdminUser();
        $role = Role::create(['name' => 'Fan']);

        $payload = [
            'name'     => 'Nuevo Usuario',
            'email'    => 'nuevo@example.com',
            'password' => 'secret123',
            'role_id'  => $role->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/users', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Usuario creado correctamente.'])
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email']
            ]);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_admin_can_update_user()
    {
        [$admin, $token] = $this->createAdminUser();
        $user = User::factory()->create([
            'name'  => 'Usuario Viejo',
            'email' => 'viejo@example.com',
        ]);

        $payload = [
            'name'  => 'Usuario Actualizado',
            'email' => 'actualizado@example.com',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/users/{$user->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Usuario actualizado correctamente.']);

        $this->assertDatabaseHas('users', ['email' => 'actualizado@example.com']);
    }

    public function test_admin_can_delete_user()
    {
        [$admin, $token] = $this->createAdminUser();
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Usuario eliminado correctamente.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_get_logs()
    {
        [$admin, $token] = $this->createAdminUser();
        $user = User::factory()->create();

        SearchLog::factory()->create([
            'user_id'     => $user->id,
            'search_type' => 'character',
            'search_id'   => 1,
        ]);
        SearchLog::factory()->create([
            'user_id'     => $admin->id,
            'search_type' => 'film',
            'search_id'   => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/logs');

        $response->assertStatus(200);
        $logs = $response->json();
        $this->assertCount(2, $logs);
    }

    public function test_admin_can_get_logs_by_user()
    {
        [$admin, $token] = $this->createAdminUser();

        $user = User::factory()->create();

        SearchLog::factory()->create([
            'user_id'     => $user->id,
            'search_type' => 'character',
            'search_id'   => 1,
        ]);
        SearchLog::factory()->create([
            'user_id'     => $user->id,
            'search_type' => 'film',
            'search_id'   => 2,
        ]);

        SearchLog::factory()->create([
            'user_id'     => $admin->id,
            'search_type' => 'planet',
            'search_id'   => 3,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/users/{$user->id}/logs");

        $response->assertStatus(200);

        $logs = $response->json();
        $this->assertCount(2, $logs);
    }
}
