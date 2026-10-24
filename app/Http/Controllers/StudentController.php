<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'course', 'year_level', 'status']);
        $perPage = (int) $request->query('per_page', 10);

        $students = $this->studentService->list($filters, $perPage);

        return response()->json([
            'data' => StudentResource::collection($students->items()),
            'meta' => [
                'current_page' => $students->currentPage(),
                'per_page'     => $students->perPage(),
                'total'        => $students->total(),
                'last_page'    => $students->lastPage(),
            ],
        ]);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->create($request->validated());

        return response()->json([
            'message' => 'Student created successfully.',
            'data'    => new StudentResource($student),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $student = $this->studentService->find($id);

        if (!$student) {
            return $this->notFound();
        }

        return response()->json(['data' => new StudentResource($student)]);
    }

    public function update(UpdateStudentRequest $request, string $id): JsonResponse
    {
        $student = $this->studentService->find($id);

        if (!$student) {
            return $this->notFound();
        }

        $updated = $this->studentService->update($student, $request->validated());

        return response()->json([
            'message' => 'Student updated successfully.',
            'data'    => new StudentResource($updated),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $student = $this->studentService->find($id);

        if (!$student) {
            return $this->notFound();
        }

        $this->studentService->delete($student);

        return response()->json([
            'message' => 'Student deleted successfully.',
        ], 200);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'data' => $this->studentService->statistics(),
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Student not found.'], 404);
    }
}