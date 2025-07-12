<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    // Return all courses as JSON for select dropdowns
    public function index(Request $request)
    {
        $courses = Course::all();
        return successResponse('Courses fetched successfully.', $courses);
    }

    /**
     * Get prerequisites for selected courses and check enrollment status.
     */
    public function getPrerequisites(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:available_courses,id',
        ]);

        try {
            $studentId = $request->student_id;
            $courseIds = $request->course_ids;

            // Get all prerequisites for the selected courses
            $prerequisites = CoursePrerequisite::with(['prerequisiteCourse', 'course'])
                ->whereIn('course_id', function($query) use ($courseIds) {
                    $query->select('course_id')
                        ->from('available_courses')
                        ->whereIn('id', $courseIds);
                })
                ->get()
                ->map(function($prereq) use ($studentId) {
                    // Check if student is enrolled in this prerequisite
                    $isEnrolled = Enrollment::where('student_id', $studentId)
                        ->where('course_id', $prereq->prerequisite_id)
                        ->exists();

                    return [
                        'course_name' => $prereq->prerequisiteCourse->name ?? 'Unknown Course',
                        'course_code' => $prereq->prerequisiteCourse->code ?? 'N/A',
                        'credit_hours' => $prereq->prerequisiteCourse->credit_hours ?? 0,
                        'required_for' => $prereq->course->name ?? 'Unknown Course',
                        'is_enrolled' => $isEnrolled,
                    ];
                })
                ->unique(function($item) {
                    return $item['course_name'] . $item['course_code'];
                })
                ->values();

            return successResponse('Prerequisites fetched successfully.', $prerequisites);
        } catch (\Exception $e) {
            logError('CourseController@getPrerequisites', $e, ['request' => $request->all()]);
            return errorResponse('Failed to fetch prerequisites.', [], 500);
        }
    }
} 