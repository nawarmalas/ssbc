<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create();
        return $this->actingAs($admin);
    }

    public function test_form_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('form_sections'));
        $this->assertTrue(Schema::hasTable('form_fields'));
        $this->assertTrue(Schema::hasTable('form_submissions'));
        $this->assertTrue(Schema::hasTable('form_answers'));
        $this->assertTrue(Schema::hasTable('form_uploads'));
    }
}
