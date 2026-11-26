<?php

namespace Tests\Feature;

use App\Models\AccessRequestHistory;
use App\Models\Notification;
use App\Models\StaffAccessRequest;
use App\Models\StaffPermissionGrant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessRequestWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->createMinimalSchema();
        $this->withoutMiddleware(\App\Http\Middleware\CheckSubscriptionNotExpired::class);

        Route::middleware(['web', 'auth', 'matrix_permission:delete_leads'])
            ->delete('/_test/leads/{id}', fn ($id) => response()->json(['deleted' => (int) $id]));
    }

    private function createMinimalSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('type')->default('staff');
            $table->integer('role')->default(5);
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('designation')->nullable();
            $table->string('avatar_path')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('users_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->boolean('view')->default(false);
            $table->boolean('create')->default(false);
            $table->boolean('edit')->default(false);
            $table->boolean('delete')->default(false);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('notification_text');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_11_26_000001_create_staff_access_request_tables.php');
        $migration->up();
    }

    private function createUsers(): array
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.local',
            'type' => 'admin',
            'role' => 1,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->assignRole($adminRole);

        $staffA = User::create([
            'name' => 'Staff A',
            'email' => 'staffa@test.local',
            'type' => 'staff',
            'role' => 5,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $staffA->assignRole($staffRole);

        $staffB = User::create([
            'name' => 'Staff B',
            'email' => 'staffb@test.local',
            'type' => 'staff',
            'role' => 5,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $staffB->assignRole($staffRole);

        return [$admin, $staffA, $staffB];
    }

    public function test_complete_request_approval_isolation_and_revocation_workflow(): void
    {
        [$admin, $staffA, $staffB] = $this->createUsers();

        // 1. Before approval: Staff A and Staff B are blocked on backend
        $this->assertFalse($staffA->hasMatrixPermission('delete_leads'));
        $this->assertFalse($staffB->hasMatrixPermission('delete_leads'));
        $this->actingAs($staffA)->delete('/_test/leads/10')->assertStatus(403);

        // 2. Staff A submits access request
        $createRes = $this->actingAs($staffA)->postJson('/access-requests', [
            'module' => 'leads',
            'action' => 'delete',
            'record_id' => 10,
            'reason' => 'Need to remove duplicate test lead #10.',
        ]);
        $createRes->assertStatus(201)->assertJsonPath('success', true);

        $requestId = $createRes->json('data.id');
        $this->assertDatabaseHas('staff_access_requests', [
            'id' => $requestId,
            'user_id' => $staffA->id,
            'permission' => 'delete_leads',
            'record_id' => 10,
            'status' => 'pending',
        ]);

        // Admin received notification
        $this->assertTrue(
            Notification::where('user_id', $admin->id)->exists()
        );

        // 3. Duplicate pending request is blocked
        $this->actingAs($staffA)->postJson('/access-requests', [
            'module' => 'leads',
            'action' => 'delete',
            'record_id' => 10,
            'reason' => 'Duplicate request.',
        ])->assertStatus(422);

        // 4. Non-admin cannot approve or view admin access requests
        $this->actingAs($staffB)->postJson("/admin/access-requests/{$requestId}/approve", [
            'comment' => 'Self approve attempt',
        ])->assertStatus(403);

        $this->actingAs($staffA)->get('/admin/access-requests')->assertStatus(403);

        // 5. Admin filters pending requests & approves Staff A's request
        $this->actingAs($admin)
            ->getJson('/admin/access-requests/list?status=pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.requests');

        $this->actingAs($admin)->postJson("/admin/access-requests/{$requestId}/approve", [
            'comment' => 'Approved for cleanup.',
        ])->assertStatus(200)->assertJsonPath('success', true);

        // 6. Staff A now has permission immediately without re-login; Staff B does NOT
        $staffA->refresh();
        $staffB->refresh();

        $this->assertTrue($staffA->hasMatrixPermission('delete_leads'));
        $this->assertTrue(Gate::forUser($staffA)->allows('leads.delete'));
        $this->assertFalse($staffB->hasMatrixPermission('delete_leads'));
        $this->assertFalse(Gate::forUser($staffB)->allows('leads.delete'));

        // Backend route allows Staff A and still blocks Staff B
        $this->actingAs($staffA)->delete('/_test/leads/10')->assertStatus(200);
        $this->actingAs($staffB)->delete('/_test/leads/10')->assertStatus(403);

        // Dynamic status endpoint reflects approval for Staff A
        $this->actingAs($staffA)
            ->getJson('/access-requests/my-status')
            ->assertStatus(200)
            ->assertJsonPath('data.permissions.leads.delete', true);

        $this->actingAs($staffB)
            ->getJson('/access-requests/my-status')
            ->assertStatus(200)
            ->assertJsonPath('data.permissions.leads.delete', false);

        // 7. Admin revokes Staff A's granted permission
        $grant = StaffPermissionGrant::where('user_id', $staffA->id)
            ->where('permission', 'delete_leads')
            ->firstOrFail();

        $this->actingAs($admin)->postJson("/admin/access-requests/grants/{$grant->id}/revoke", [
            'reason' => 'Cleanup finished.',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $staffA->refresh();
        $this->assertFalse($staffA->hasMatrixPermission('delete_leads'));
        $this->actingAs($staffA)->delete('/_test/leads/10')->assertStatus(403);

        // Audit trail recorded requested, approved, revoked
        $events = AccessRequestHistory::where('user_id', $staffA->id)
            ->orderBy('id')
            ->pluck('event')
            ->all();
        $this->assertSame(['requested', 'approved', 'revoked'], $events);
    }

    public function test_rejection_workflow_keeps_action_restricted(): void
    {
        [$admin, $staffA] = $this->createUsers();

        $res = $this->actingAs($staffA)->postJson('/access-requests', [
            'module' => 'policies',
            'action' => 'export',
            'reason' => 'Need policy export.',
        ])->assertStatus(201);

        $requestId = $res->json('data.id');

        $this->actingAs($admin)->postJson("/admin/access-requests/{$requestId}/reject", [
            'comment' => 'Export restricted to managers.',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $staffA->refresh();
        $this->assertFalse($staffA->hasMatrixPermission('export_policies'));
        $this->assertDatabaseHas('staff_access_requests', [
            'id' => $requestId,
            'status' => 'rejected',
            'admin_comment' => 'Export restricted to managers.',
        ]);
    }

    public function test_unchecking_permission_in_team_member_revokes_active_grant(): void
    {
        [$admin, $staffA] = $this->createUsers();

        $res = $this->actingAs($staffA)->postJson('/access-requests', [
            'module' => 'leads',
            'action' => 'delete',
            'reason' => 'Need temporary delete access.',
        ])->assertStatus(201);

        $this->actingAs($admin)->postJson("/admin/access-requests/{$res->json('data.id')}/approve")
            ->assertStatus(200);

        $staffA->refresh();
        $this->assertTrue($staffA->hasMatrixPermission('delete_leads'));

        // Admin unchecks delete_leads in Team Member edit (only keeps view_leads)
        app(\App\Services\TeamMemberService::class)->updateMember($staffA->id, [
            'first_name' => 'Staff',
            'last_name' => 'A',
            'email' => $staffA->email,
            'status' => 'Active',
            'role' => 5,
            'permissions' => ['view_leads'],
        ], null);

        $staffA->refresh();
        $this->assertFalse($staffA->hasMatrixPermission('delete_leads'));
        $this->assertDatabaseHas('staff_permission_grants', [
            'user_id' => $staffA->id,
            'permission' => 'delete_leads',
            'is_active' => 0,
        ]);
    }

    public function test_batch_checkbox_access_request_creates_requests_for_checked_actions(): void
    {
        [$admin, $staffA] = $this->createUsers();

        $res = $this->actingAs($staffA)->postJson('/access-requests', [
            'module' => 'leads',
            'actions' => ['delete', 'export'],
            'reason' => 'Need delete and export for monthly cleanup.',
        ]);

        $res->assertStatus(201)->assertJsonPath('success', true);
        $this->assertCount(2, $res->json('items'));

        $this->assertDatabaseHas('staff_access_requests', [
            'user_id' => $staffA->id,
            'permission' => 'delete_leads',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('staff_access_requests', [
            'user_id' => $staffA->id,
            'permission' => 'export_leads',
            'status' => 'pending',
        ]);
    }
}
