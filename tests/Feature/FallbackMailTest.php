<?php

namespace Tests\Feature;

use App\Jobs\SendFallbackMailableJob;
use App\Mail\TicketCreatedMail;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\FallbackMailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FallbackMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'mail.default' => 'smtp',
        ]);

        $this->createSchema();
        $this->withoutMiddleware(\App\Http\Middleware\CheckSubscriptionNotExpired::class);
    }

    private function createSchema(): void
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

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('notification_text');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    private function seedMailSettings(array $overrides = []): void
    {
        $defaults = [
            'mail_host' => 'smtp.primary.test',
            'mail_port' => '587',
            'mail_username' => 'primary@test.local',
            'mail_password' => 'primary-secret-pass',
            'mail_encryption' => 'tls',
            'mail_from_name' => 'Primary CRM',
            'mail_fallback_enabled' => '1',
            'mail_fallback_email' => 'backup@test.local',
            'mail_fallback_host' => 'smtp.backup.test',
            'mail_fallback_port' => '465',
            'mail_fallback_username' => 'backup@test.local',
            'mail_fallback_password' => app(FallbackMailService::class)->encryptSecret('backup-secret-pass'),
            'mail_fallback_encryption' => 'ssl',
            'mail_fallback_from_address' => 'backup@test.local',
            'mail_fallback_from_name' => 'Backup CRM',
        ];

        foreach (array_merge($defaults, $overrides) as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'smtp', 'type' => 'string']
            );
        }
    }

    public function test_primary_smtp_succeeds_and_backup_is_not_triggered(): void
    {
        $this->seedMailSettings();
        $attemptedMailers = [];

        $result = app(FallbackMailService::class)->send(function (string $mailer) use (&$attemptedMailers) {
            $attemptedMailers[] = $mailer;
        });

        $this->assertTrue($result);
        $this->assertSame(['smtp'], $attemptedMailers);
    }

    public function test_primary_smtp_fails_and_backup_smtp_succeeds(): void
    {
        $this->seedMailSettings();
        $attemptedMailers = [];
        $capturedFrom = null;

        Log::spy();

        $result = app(FallbackMailService::class)->send(function (string $mailer, ?string $fromAddress) use (&$attemptedMailers, &$capturedFrom) {
            $attemptedMailers[] = $mailer;
            if ($mailer === 'smtp') {
                throw new \RuntimeException('Connection timed out to smtp.primary.test');
            }
            $capturedFrom = $fromAddress;
        });

        $this->assertTrue($result);
        $this->assertSame(['smtp', 'smtp_fallback'], $attemptedMailers);
        $this->assertSame('backup@test.local', $capturedFrom);
        $this->assertSame('smtp.backup.test', Config::get('mail.mailers.smtp_fallback.host'));
        $this->assertSame(465, Config::get('mail.mailers.smtp_fallback.port'));
        $this->assertSame('backup-secret-pass', Config::get('mail.mailers.smtp_fallback.password'));
    }

    public function test_both_smtp_servers_fail_logs_error_and_returns_false(): void
    {
        $this->seedMailSettings();
        Log::spy();

        $result = app(FallbackMailService::class)->send(function (string $mailer) {
            throw new \RuntimeException("SMTP failure on {$mailer}");
        });

        $this->assertFalse($result);
        Log::shouldHaveReceived('warning')->once();
        Log::shouldHaveReceived('error')->once();
    }

    public function test_fallback_disabled_uses_only_primary_smtp(): void
    {
        $this->seedMailSettings(['mail_fallback_enabled' => '0']);
        $attemptedMailers = [];

        $result = app(FallbackMailService::class)->send(function (string $mailer) use (&$attemptedMailers) {
            $attemptedMailers[] = $mailer;
            throw new \RuntimeException('Primary SMTP error');
        });

        $this->assertFalse($result);
        $this->assertSame(['smtp'], $attemptedMailers);
    }

    public function test_empty_fallback_settings_continues_normally_with_primary(): void
    {
        $this->seedMailSettings([
            'mail_fallback_enabled' => '1',
            'mail_fallback_email' => '',
            'mail_fallback_from_address' => '',
        ]);
        $attemptedMailers = [];

        $result = app(FallbackMailService::class)->send(function (string $mailer) use (&$attemptedMailers) {
            $attemptedMailers[] = $mailer;
        });

        $this->assertTrue($result);
        $this->assertSame(['smtp'], $attemptedMailers);
    }

    public function test_queued_job_uses_fallback_when_primary_fails_without_duplicates(): void
    {
        $this->seedMailSettings();
        Mail::fake();

        $ticket = new SupportTicket(['name' => 'TKT-1001']);
        $ticket->id = 1001;

        $service = $this->mock(FallbackMailService::class);
        $service->shouldReceive('sendMailable')
            ->once()
            ->withArgs(function ($to, $mailable, $context) {
                return $to === 'client@example.com'
                    && $mailable instanceof TicketCreatedMail
                    && ($context['queued'] ?? false) === true;
            })
            ->andReturn(true);

        $job = new SendFallbackMailableJob('client@example.com', new TicketCreatedMail($ticket));
        $job->handle($service);
    }

    public function test_admin_can_save_fallback_settings_and_passwords_are_not_exposed(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'type' => 'admin',
            'role' => 1,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->assignRole($adminRole);

        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@test.local',
            'type' => 'staff',
            'role' => 5,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $staff->assignRole($staffRole);

        // Non-admin blocked
        $this->actingAs($staff)->putJson('/settings', [
            'mail_fallback_enabled' => '1',
            'mail_fallback_email' => 'backup@test.local',
        ])->assertStatus(403);

        // Invalid email rejected
        $this->actingAs($admin)->putJson('/settings', [
            'mail_fallback_enabled' => '1',
            'mail_fallback_email' => 'not-an-email',
        ])->assertStatus(422);

        // Admin saves valid fallback settings
        $this->actingAs($admin)->putJson('/settings', [
            'mail_host' => 'smtp.primary.test',
            'mail_port' => 587,
            'mail_username' => 'primary@test.local',
            'mail_encryption' => 'tls',
            'mail_from_name' => 'Riskon CRM',
            'mail_fallback_enabled' => '1',
            'mail_fallback_email' => 'backup@test.local',
            'mail_fallback_host' => 'smtp.backup.test',
            'mail_fallback_port' => 465,
            'mail_fallback_username' => 'backup@test.local',
            'mail_fallback_password' => 'super-secret-backup-pw',
            'mail_fallback_encryption' => 'ssl',
            'mail_fallback_from_address' => 'backup@test.local',
            'mail_fallback_from_name' => 'Riskon Backup',
        ])->assertStatus(200)->assertJsonPath('success', true);

        // Stored password is encrypted, not plaintext
        $stored = Setting::where('key', 'mail_fallback_password')->value('value');
        $this->assertNotSame('super-secret-backup-pw', $stored);
        $this->assertSame('super-secret-backup-pw', app(FallbackMailService::class)->decryptSecret($stored));

        // API settings response does NOT expose passwords
        $apiRes = $this->actingAs($admin)->getJson(route('api.settings.index'))->assertStatus(200);
        $returnedKeys = collect($apiRes->json('data'))->pluck('key')->all();
        $this->assertNotContains('mail_password', $returnedKeys);
        $this->assertNotContains('mail_fallback_password', $returnedKeys);
        $this->assertContains('mail_fallback_email', $returnedKeys);
    }
}
