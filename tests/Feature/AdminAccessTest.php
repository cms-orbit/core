<?php

namespace CmsOrbit\Core\Tests\Feature;

use CmsOrbit\Core\Entities\Role\Role;
use CmsOrbit\Core\Entities\User\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate', ['--database' => 'testing']);

        // Create super admin
        $this->superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => [
                'platform.index' => true,
                'platform.systems.index' => true,
                'orbit.entities.users' => true,
                'orbit.entities.users.create' => true,
                'orbit.entities.users.edit' => true,
                'orbit.entities.users.remove' => true,
                'orbit.entities.users.impersonate' => true,
                'orbit.entities.roles' => true,
                'orbit.entities.roles.create' => true,
                'orbit.entities.roles.edit' => true,
                'orbit.entities.roles.remove' => true,
            ],
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->admin->addRole($this->superAdminRole);
    }

    /** @test */
    public function guest_is_redirected_to_login()
    {
        $response = $this->get(config('orbit.prefix'));
        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_can_login_and_access_admin_panel()
    {
        // Login via POST
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        // Access admin panel
        $response = $this->get(config('orbit.prefix'));
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)->get(config('orbit.prefix'));
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_users_list()
    {
        $response = $this->actingAs($this->admin)->get(config('orbit.prefix') . '/users');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_users_create()
    {
        $response = $this->actingAs($this->admin)->get(config('orbit.prefix') . '/users/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_users_edit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->get(config('orbit.prefix') . "/users/{$user->id}/edit");
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_roles_list()
    {
        $response = $this->actingAs($this->admin)->get(config('orbit.prefix') . '/roles');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_roles_create()
    {
        $response = $this->actingAs($this->admin)->get(config('orbit.prefix') . '/roles/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_roles_edit()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'permissions' => [],
        ]);

        $response = $this->actingAs($this->admin)->get(config('orbit.prefix') . "/roles/{$role->id}/edit");
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_save_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(config('orbit.prefix') . "/users/{$user->id}/edit", [
            'user' => [
                'name' => 'Updated Name',
                'email' => $user->email,
            ],
        ]);

        $response->assertRedirect(config('orbit.prefix') . '/users');
        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    /** @test */
    public function authenticated_admin_can_save_role()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'permissions' => [],
        ]);

        $response = $this->actingAs($this->admin)->post(config('orbit.prefix') . "/roles/{$role->id}/edit", [
            'role' => [
                'name' => 'Updated Role',
                'slug' => 'updated-role',
            ],
            'permissions' => [],
        ]);

        $response->assertRedirect(config('orbit.prefix') . '/roles');
        $this->assertEquals('Updated Role', $role->fresh()->name);
    }

    /** @test */
    public function authenticated_admin_can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(config('orbit.prefix') . "/users/{$user->id}/edit/remove");

        $response->assertRedirect(config('orbit.prefix') . '/users');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function authenticated_admin_can_delete_role()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'permissions' => [],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(config('orbit.prefix') . "/roles/{$role->id}/edit/remove");

        $response->assertRedirect(config('orbit.prefix') . '/roles');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function user_without_permissions_cannot_access_admin_panel()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(config('orbit.prefix'));
        $response->assertStatus(403);
    }

    /** @test */
    public function user_without_permissions_cannot_access_users_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(config('orbit.prefix') . '/users');
        $response->assertStatus(403);
    }

    /** @test */
    public function user_without_permissions_cannot_access_roles_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(config('orbit.prefix') . '/roles');
        $response->assertStatus(403);
    }

    /** @test */
    public function comprehensive_admin_routes_test()
    {
        $routes = [
            'GET ' . config('orbit.prefix') => 200,
            'GET ' . config('orbit.prefix') . '/users' => 200,
            'GET ' . config('orbit.prefix') . '/users/create' => 200,
            'GET ' . config('orbit.prefix') . '/roles' => 200,
            'GET ' . config('orbit.prefix') . '/roles/create' => 200,
        ];

        foreach ($routes as $route => $expectedStatus) {
            [$method, $path] = explode(' ', $route);

            $response = $this->actingAs($this->admin)->call($method, $path);

            $this->assertEquals(
                $expectedStatus,
                $response->status(),
                "Route {$method} {$path} returned {$response->status()} instead of {$expectedStatus}"
            );
        }
    }
}
