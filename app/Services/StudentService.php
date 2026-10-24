<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StudentService
{
    private const MIN_AGE = 15;

    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository
    ) {}

    public function list(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->studentRepository->paginate($filters, $perPage);
    }

    public function find(string $id): ?Student
    {
        return $this->studentRepository->find($id);
    }

    public function create(array $data): Student
    {
        $this->assertMinimumAge($data['age']);

        return $this->studentRepository->create($data);
    }

    public function update(Student $student, array $data): Student
    {
        if (array_key_exists('age', $data)) {
            $this->assertMinimumAge($data['age']);
        }

        return $this->studentRepository->update($student, $data);
    }

    public function delete(Student $student): bool
    {
        return $this->studentRepository->delete($student);
    }

    public function statistics(): array
    {
        return $this->studentRepository->statistics();
    }

    /**
     * Business rule: a student must be at least 15 years old to be registered.
     * Centralized here so no controller (or future service) needs to repeat it.
     */
    private function assertMinimumAge(int $age): void
    {
        if ($age < self::MIN_AGE) {
            throw ValidationException::withMessages([
                'age' => 'A student must be at least ' . self::MIN_AGE . ' years old.',
            ]);
        }
    }
}