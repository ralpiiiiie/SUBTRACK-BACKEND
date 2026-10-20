<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'age'        => 20,
            'course'     => 'Computer Science',
            'year_level' => 2,
            'status'     => 'active',
        ], $overrides);
    }

    public function test_creates_a_student(): void
    {
        $this->postJson('/api/students', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.email', 'john@example.com');

        $this->assertDatabaseHas('students', ['email' => 'john@example.com']);
    }

    public function test_rejects_invalid_data(): void
    {
        $this->postJson('/api/students', $this->payload(['age' => 10]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('age');
    }

    public function test_rejects_duplicate_email(): void
    {
        Student::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/students', $this->payload(['email' => 'dup@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_lists_students_paginated(): void
    {
        Student::factory()->count(15)->create();

        $this->getJson('/api/students?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15);
    }

    public function test_shows_a_student(): void
    {
        $student = Student::factory()->create();

        $this->getJson("/api/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $student->id);
    }

    public function test_returns_404_for_missing_student(): void
    {
        $this->getJson('/api/students/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_updates_a_student(): void
    {
        $student = Student::factory()->create();

        $this->putJson("/api/students/{$student->id}", ['first_name' => 'Jane'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Jane');
    }

    public function test_update_validates_invalid_data(): void
    {
        $student = Student::factory()->create();

        $this->putJson("/api/students/{$student->id}", ['age' => 5])
            ->assertStatus(422);
    }

    public function test_update_allows_keeping_existing_email(): void
    {
        $student = Student::factory()->create(['email' => 'keep@example.com']);

        $this->putJson("/api/students/{$student->id}", [
            'email' => 'keep@example.com',
            'first_name' => 'Keptname',
        ])->assertOk();
    }

    public function test_deletes_a_student(): void
    {
        $student = Student::factory()->create();

        $this->deleteJson("/api/students/{$student->id}")->assertOk();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_deleting_missing_student_returns_404(): void
    {
        $this->deleteJson('/api/students/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }
}