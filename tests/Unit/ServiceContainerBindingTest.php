<?php

namespace Tests\Unit;

use App\Repositories\Contracts\StudentRepositoryInterface;
use App\Repositories\StudentRepository;
use App\Services\StudentService;
use Tests\TestCase;

class ServiceContainerBindingTest extends TestCase
{
    public function test_repository_interface_resolves_to_concrete_repository(): void
    {
        $resolved = app(StudentRepositoryInterface::class);

        $this->assertInstanceOf(StudentRepository::class, $resolved);
    }

    public function test_service_receives_repository_via_the_interface(): void
    {
        $service = app(StudentService::class);

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('studentRepository');
        $property->setAccessible(true);

        $this->assertInstanceOf(
            StudentRepositoryInterface::class,
            $property->getValue($service)
        );
    }
}