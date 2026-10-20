<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'age',
        'course',
        'year_level',
        'status',
    ];

    protected $casts = [
        'age'        => 'integer',
        'year_level' => 'integer',
    ];

    // Scopes keep query fragments reusable and out of the repository's
    // main methods — still pure Eloquent, no business logic.
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function scopeCourse($query, ?string $course)
    {
        return $course ? $query->where('course', $course) : $query;
    }

    public function scopeYearLevel($query, ?int $yearLevel)
    {
        return $yearLevel ? $query->where('year_level', $yearLevel) : $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }
}