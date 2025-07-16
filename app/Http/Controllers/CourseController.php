<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\CourseService;
use App\Models\Course;
use App\Exceptions\BusinessValidationException;
use Exception;

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
     * Display the course management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('course.index');
    }

    /**
     * Get course statistics.
     *
     * @return JsonResponse
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
     * Get course data for DataTables.
     *
     * @return JsonResponse
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
     * Store a newly created course.
     *
     * @param Request $request
     * @return JsonResponse
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
     * Display the specified course.
     *
     * @param Course $course
     * @return JsonResponse
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
     * Update the specified course.
     *
     * @param Request $request
     * @param Course $course
     * @return JsonResponse
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
     * Remove the specified course.
     *
     * @param Course $course
     * @return JsonResponse
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
     * Get all faculties for dropdown.
     *
     * @return JsonResponse
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
     * Get all courses for prerequisite dropdown.
     *
     * @return JsonResponse
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

    /**
     * Get all courses (for dropdown and forms).
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $courses = $this->courseService->getAll();
            return successResponse('Courses fetched successfully.', $courses);
        } catch (Exception $e) {
            logError('CourseController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get prerequisites for selected courses and check enrollment status.
     *
     * @param Request $request
     * @return JsonResponse
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

            $prerequisites = $this->courseService->getPrerequisites($studentId, $courseIds);
            return successResponse('Prerequisites fetched successfully.', $prerequisites);
        } catch (Exception $e) {
            logError('CourseController@getPrerequisites', $e, ['request' => $request->all()]);
            return errorResponse('Failed to fetch prerequisites.', [], 500);
        }
    }
} 