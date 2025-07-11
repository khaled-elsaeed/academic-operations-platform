<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class CourseController extends Controller
{
    /**
     * Display the course management page
     */
    public function index()
    {
        return view('admin.course');
    }

    /**
     * Get course statistics
     */
    public function stats(): JsonResponse
    {
        $totalCourses = Course::count();
        $coursesWithPrerequisites = Course::has('prerequisites')->count();
        $coursesWithoutPrerequisites = Course::doesntHave('prerequisites')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $totalCourses,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'withPrerequisites' => [
                    'total' => $coursesWithPrerequisites,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'withoutPrerequisites' => [
                    'total' => $coursesWithoutPrerequisites,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ]
            ]
        ]);
    }

    /**
     * Get course data for DataTables
     */
    public function datatable(): JsonResponse
    {
        $courses = Course::with(['program', 'prerequisites']);

        return DataTables::of($courses)
            ->addColumn('program_name', function ($course) {
                return $course->program ? $course->program->name : 'N/A';
            })
            ->addColumn('prerequisites_count', function ($course) {
                return $course->prerequisites->count();
            })
            ->addColumn('prerequisites_list', function ($course) {
                return $course->prerequisites->pluck('title')->join(', ') ?: 'None';
            })
            ->addColumn('action', function ($course) {
                return '
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item editCourseBtn" href="javascript:void(0);" data-id="' . $course->id . '">
                                <i class="bx bx-edit-alt me-1"></i> Edit
                            </a>
                            <a class="dropdown-item deleteCourseBtn" href="javascript:void(0);" data-id="' . $course->id . '">
                                <i class="bx bx-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:courses,code',
            'title' => 'required|string|max:255',
            'credit_hours' => 'required|numeric|min:0|max:99',
            'program_id' => 'required|exists:programs,id'
        ]);

        Course::create([
            'code' => $request->code,
            'title' => $request->title,
            'credit_hours' => $request->credit_hours,
            'program_id' => $request->program_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.'
        ]);
    }

    /**
     * Display the specified course
     */
    public function show(Course $course): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $course->load(['program', 'prerequisites'])
        ]);
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, Course $course): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:courses,code,' . $course->id,
            'title' => 'required|string|max:255',
            'credit_hours' => 'required|numeric|min:0|max:99',
            'program_id' => 'required|exists:programs,id'
        ]);

        $course->update([
            'code' => $request->code,
            'title' => $request->title,
            'credit_hours' => $request->credit_hours,
            'program_id' => $request->program_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.'
        ]);
    }

    /**
     * Remove the specified course
     */
    public function destroy(Course $course): JsonResponse
    {
        // Check if course has dependent courses (is a prerequisite for other courses)
        if ($course->dependentCourses()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete course that is a prerequisite for other courses.'
            ], 422);
        }

        // Check if course has available courses
        if ($course->availableCourses()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete course that has available course instances.'
            ], 422);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.'
        ]);
    }

    /**
     * Get all programs for dropdown
     */
    public function getPrograms(): JsonResponse
    {
        $programs = Program::all();
        return response()->json([
            'success' => true,
            'data' => $programs
        ]);
    }

    /**
     * Get all courses for prerequisite dropdown
     */
    public function getCourses(): JsonResponse
    {
        $courses = Course::all();
        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }
} 