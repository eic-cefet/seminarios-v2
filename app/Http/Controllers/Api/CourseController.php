<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * List all courses
     */
    public function index(): JsonResponse
    {
        $courses = Course::orderBy('name')->get();

        return response()->json([
            'data' => $courses->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->name,
            ]),
        ]);
    }
}
