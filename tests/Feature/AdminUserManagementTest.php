<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_screen(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_subadmin_cannot_access_users_routes(): void
    {
        $subadmin = User::factory()->create(['role' => User::ROLE_SUBADMIN]);

        $this->actingAs($subadmin)->get('/admin/users')->assertForbidden();
        $this->actingAs($subadmin)->get('/admin/users/create')->assertForbidden();
    }

    public function test_admin_can_create_subadmin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'News Editor',
            'email' => 'editor@example.com',
            'role' => User::ROLE_SUBADMIN,
            'is_active' => '1',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ])->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'email' => 'editor@example.com',
            'role' => User::ROLE_SUBADMIN,
            'is_active' => true,
        ]);

        $subadmin = User::where('email', 'editor@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Secret123!', $subadmin->password));
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_SUBADMIN,
            'is_active' => false,
        ]);

        $this->post('/admin/login', [
            'email' => 'inactive@example.com',
            'password' => 'Secret123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_reset_subadmin_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $subadmin = User::factory()->create([
            'role' => User::ROLE_SUBADMIN,
            'password' => Hash::make('OldSecret123!'),
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $subadmin), [
            'name' => $subadmin->name,
            'email' => $subadmin->email,
            'role' => User::ROLE_SUBADMIN,
            'is_active' => '1',
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertRedirect('/admin/users');

        $this->assertTrue(Hash::check('NewSecret123!', $subadmin->fresh()->password));
    }

    public function test_current_admin_cannot_disable_or_delete_self(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_ADMIN,
            'is_active' => '0',
        ])->assertSessionHas('error');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_last_active_admin_cannot_be_removed_or_downgraded(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $subadmin = User::factory()->create(['role' => User::ROLE_SUBADMIN]);

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_SUBADMIN,
            'is_active' => '1',
        ])->assertSessionHas('error');

        $this->actingAs($admin)->patch(route('admin.users.update', $subadmin), [
            'name' => $subadmin->name,
            'email' => $subadmin->email,
            'role' => User::ROLE_ADMIN,
            'is_active' => '1',
        ])->assertRedirect('/admin/users');

        // With $subadmin promoted, $admin is no longer the last active admin
        // and can be removed — by the other admin, since the controller
        // forbids deleting your own account.
        $this->actingAs($subadmin->fresh())->delete(route('admin.users.destroy', $admin))
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $subadmin->id,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }
}
