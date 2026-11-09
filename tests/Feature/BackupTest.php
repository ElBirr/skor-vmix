<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_admin_can_download_sqlite_backup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/backup')->assertOk();

        $this->assertStringStartsWith(
            'attachment; filename=skor-vmix-',
            $response->headers->get('content-disposition')
        );
    }
}
