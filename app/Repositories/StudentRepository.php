<?php

namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentRepository implements StudentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Student::query()
            ->search($filters['search'] ?? null)
            ->course($filters['course'] ?? null)
            ->yearLevel($filters['year_level'] ?? null)
            ->status($filters['status'] ?? null)
            ->latest()
            ->paginate($perPage)
            ->appends($filters);
    }

    public function find(string $id): ?Student
    {
        return Student::find($id);
    }

    public function create(array $data): Student
    {
        return Student::create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        return $student->fresh();
    }

    public function delete(Student $student): bool
    {
        return (bool) $student->delete();
    }

    public function statistics(): array
    {
        return [
            'total_students'    => Student::count(),
            'active_students'   => Student::where('status', 'active')->count(),
            'inactive_students' => Student::where('status', 'inactive')->count(),
        ];
    }
}