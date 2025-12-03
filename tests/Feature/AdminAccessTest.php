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
        $response = $this->get('/orbit-settings');
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
        $response = $this->get('/orbit-settings');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin)->get('/orbit-settings');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_users_list()
    {
        $response = $this->actingAs($this->admin)->get('/orbit-settings/users');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_users_create()
    {
        $response = $this->actingAs($this->admin)->get('/orbit-settings/users/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_users_edit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->get("/orbit-settings/users/{$user->id}/edit");
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_roles_list()
    {
        $response = $this->actingAs($this->admin)->get('/orbit-settings/roles');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_roles_create()
    {
        $response = $this->actingAs($this->admin)->get('/orbit-settings/roles/create');
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

        $response = $this->actingAs($this->admin)->get("/orbit-settings/roles/{$role->id}/edit");
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_save_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post("/orbit-settings/users/{$user->id}/edit", [
            'user' => [
                'name' => 'Updated Name',
                'email' => $user->email,
            ],
        ]);

        $response->assertRedirect('/orbit-settings/users');
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

        $response = $this->actingAs($this->admin)->post("/orbit-settings/roles/{$role->id}/edit", [
            'role' => [
                'name' => 'Updated Role',
                'slug' => 'updated-role',
            ],
            'permissions' => [],
        ]);

        $response->assertRedirect('/orbit-settings/roles');
        $this->assertEquals('Updated Role', $role->fresh()->name);
    }

    /** @test */
    public function authenticated_admin_can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post("/orbit-settings/users/{$user->id}/edit/remove");

        $response->assertRedirect('/orbit-settings/users');
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
            ->post("/orbit-settings/roles/{$role->id}/edit/remove");

        $response->assertRedirect('/orbit-settings/roles');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function user_without_permissions_cannot_access_admin_panel()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/orbit-settings');
        $response->assertStatus(403);
    }

    /** @test */
    public function user_without_permissions_cannot_access_users_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/orbit-settings/users');
        $response->assertStatus(403);
    }

    /** @test */
    public function user_without_permissions_cannot_access_roles_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/orbit-settings/roles');
        $response->assertStatus(403);
    }

    /** @test */
    public function comprehensive_admin_routes_test()
    {
        $routes = [
            'GET /orbit-settings' => 200,
            'GET /orbit-settings/users' => 200,
            'GET /orbit-settings/users/create' => 200,
            'GET /orbit-settings/roles' => 200,
            'GET /orbit-settings/roles/create' => 200,
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
