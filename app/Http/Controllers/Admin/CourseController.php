<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\Admin\CourseService;
use Exception;
use App\Exceptions\BusinessValidationException;

class CourseController extends Controller
{
    /**
     * CourseController constructor.
     *
     * @param CourseService $courseService
     */
    public function __construct(protected CourseService $courseService)
    {}

    /**
     * Display the course management page
     */
    public function index(): View
    {
        return view('admin.course');
    }

    /**
     * Get course statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->courseService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('CourseController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get course data for DataTables
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->courseService->getDatatable();
        } catch (Exception $e) {
            logError('CourseController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
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
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        try {
            $validated = $request->all();
            $course = $this->courseService->createCourse($validated);
            return successResponse('Course created successfully.', $course);
        } catch (Exception $e) {
            logError('CourseController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified course
     */
    public function show(Course $course): JsonResponse
    {
        try {
            $course = $this->courseService->getCourse($course);
            return successResponse('Course details fetched successfully.', $course);
        } catch (Exception $e) {
            logError('CourseController@show', $e, ['course_id' => $course->id]);
            return errorResponse('Internal server error.', [], 500);
        }
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
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        try {
            $validated = $request->all();
            $course = $this->courseService->updateCourse($course, $validated);
            return successResponse('Course updated successfully.', $course);
        } catch (Exception $e) {
            logError('CourseController@update', $e, ['course_id' => $course->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Remove the specified course
     */
    public function destroy(Course $course): JsonResponse
    {
        try {
            $this->courseService->deleteCourse($course);
            return successResponse('Course deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('CourseController@destroy', $e, ['course_id' => $course->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all faculties for dropdown
     */
    public function getFaculties(): JsonResponse
    {
        try {
            $faculties = $this->courseService->getFaculties();
            return successResponse('Faculties fetched successfully.', $faculties);
        } catch (Exception $e) {
            logError('CourseController@getFaculties', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all courses for prerequisite dropdown
     */
    public function getCourses(): JsonResponse
    {
        try {
            $courses = $this->courseService->getCourses();
            return successResponse('Courses fetched successfully.', $courses);
        } catch (Exception $e) {
            logError('CourseController@getCourses', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 