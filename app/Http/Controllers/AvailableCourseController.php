<?php

namespace App\Http\Controllers;

use App\Models\AvailableCourse;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailableCourseController extends Controller
{
    /**
     * Display a listing of the available courses for a given student and term.
     * Returns only the related course models.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validate request input
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id'    => ['required', 'exists:terms,id'],
        ]);

        // Retrieve student and relevant IDs
        $student = Student::findOrFail($validated['student_id']);
        $programId = $student->program_id;
        $levelId   = $student->level_id;
        $termId    = $validated['term_id'];

        // Query available courses for the student's program, level, and term
        $availableCourses = AvailableCourse::available($programId, $levelId, $termId)
            ->with('course')
            ->get();

        // Map to course data structure
        $courses = $availableCourses->map(function ($availableCourse) {
            return [
                'id'                 => $availableCourse->course->id,
                'name'               => $availableCourse->course->name,
                'code'               => $availableCourse->course->code,
                'available_course_id'=> $availableCourse->id,
                'remaining_capacity' => $availableCourse->remaining_capacity,
            ];
        });

        // Return JSON response
        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }
}