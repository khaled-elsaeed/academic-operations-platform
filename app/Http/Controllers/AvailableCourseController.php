<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\AvailableCourse\AvailableCourseService;
use App\Exports\AvailableCoursesTemplateExport;
use App\Http\Requests\{StoreAvailableCourseRequest, UpdateAvailableCourseRequest};
use App\Models\AvailableCourse;
use App\Models\Student;
use App\Models\Term;
use App\Exceptions\BusinessValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class AvailableCourseController extends Controller
{
    /**
     * AvailableCourseController constructor.
     *
     * @param AvailableCourseService $availableCourseService
     */
    public function __construct(protected AvailableCourseService $availableCourseService)
    {}

    /**
     * Display the available courses page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('available_course.index');
    }

    /**
     * Return data for DataTable AJAX requests.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->availableCourseService->getDatatable();
        } catch (Exception $e) {
            logError('AvailableCourseController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show the form for creating a new available course.
     *
     * @return View
     */
    public function create(): View
    {
        return view('available_course.create');
    }

    /**
     * Store a new available course.
     *
     * @param StoreAvailableCourseRequest $request
     * @return JsonResponse
     */
    public function store(StoreAvailableCourseRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $availableCourse = $this->availableCourseService->createAvailableCourse($validated);
            return successResponse('Available course created successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show the form for editing the specified available course.
     *
     * @param int $id
     * @return View
     */
    public function edit($id): View
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return view('available_course.edit', compact('availableCourse'));
    }

    /**
     * Update the specified available course in storage.
     *
     * @param UpdateAvailableCourseRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateAvailableCourseRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $availableCourse = $this->availableCourseService->updateAvailableCourse($id, $validated);
            return successResponse('Available course updated successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@update', $e, ['id' => $id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete an available course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->availableCourseService->deleteAvailableCourse($id);
            return successResponse('Available course deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@destroy', $e, ['id' => $id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified available course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $availableCourse = $this->availableCourseService->getAvailableCourse($id);
            return successResponse('Available course retrieved successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@show', $e, ['id' => $id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Import available courses from an Excel file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'courses_file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $result = $this->availableCourseService->importAvailableCoursesFromFile($request->file('courses_file'));
            $importedCount = ($result['summary']['total_created'] ?? 0) + ($result['summary']['total_updated'] ?? 0);
            return successResponse($result['message'], [
                'imported_count' => $importedCount,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (Exception $e) {
            logError('AvailableCourseController@import', $e, ['request' => $request->all()]);
            return errorResponse('Import failed. Please check your file.', [], 500);
        }
    }

    /**
     * Download the available courses import template as an Excel file.
     *
     * @return BinaryFileResponse
     */
    public function template(): BinaryFileResponse
    {
        try {
            return Excel::download(new AvailableCoursesTemplateExport, 'available_courses_template.xlsx');
        } catch (Exception $e) {
            logError('AvailableCourseController@template', $e);
            throw $e;
        }
    }

    /**
     * Get all available courses (admin and legacy student-specific).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        try {
            if ($request->has('student_id') && $request->has('term_id')) {
                return $this->getStudentAvailableCourses($request);
            }
            $courses = $this->availableCourseService->getAll();
            return successResponse('Available courses retrieved successfully.', $courses);
        } catch (Exception $e) {
            logError('AvailableCourseController@all', $e);
            return errorResponse('Failed to retrieve available courses.', [], 500);
        }
    }

    /**
     * Get available courses for a specific student and term (legacy functionality).
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getStudentAvailableCourses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id'    => ['required', 'exists:terms,id'],
            'exceptionForDifferentLevels'  => ['nullable', 'boolean'],
        ]);
        $student = Student::findOrFail($validated['student_id']);
        $programId = $student->program_id;
        $levelId   = $student->level_id;
        $termId    = $validated['term_id'];
        $studentId = $validated['student_id'];
        $exceptionForDifferentLevels = $validated['exceptionForDifferentLevels'];
        $availableCourses = AvailableCourse::available($programId, $levelId, $termId, $exceptionForDifferentLevels)
            ->notEnrolled($studentId, $termId)
            ->with(['course', 'eligibilities', 'schedules'])
            ->get();
        $courses = $availableCourses->map(function ($availableCourse) use ($programId, $levelId) {
            // Find all eligibility groups for this student's program and level (may be multiple)
            $eligibilitiesForStudent = $availableCourse->eligibilities->filter(function ($elig) use ($programId, $levelId) {
                return $elig->program_id == $programId && $elig->level_id == $levelId;
            })->values();

            $groups = $eligibilitiesForStudent->pluck('group')->unique()->values()->all();

            // Determine remaining capacity based on schedule-level capacities when present.
            // If schedules with max_capacity exist, compute remaining per schedule for the given term
            $scheduleRemains = [];
                $totalRemaining = 0;
                foreach ($availableCourse->schedules as $sched) {
                    if ($sched->max_capacity !== null && $sched->max_capacity !== '') {
                        $enrolledCount = \App\Models\EnrollmentSchedule::where('available_course_schedule_id', $sched->id)
                            ->whereHas('enrollment', function ($q) use ($availableCourse) {
                                $q->where('term_id', $availableCourse->term_id);
                            })->count();
                        $rem = (int)$sched->max_capacity - $enrolledCount;
                        if ($rem > 0) {
                            $totalRemaining += $rem;
                        }
                    }
                }

                // If any schedule produced a positive remaining, use the sum. Otherwise fallback to the course remaining_capacity.
                $remainingCapacityValue = $totalRemaining > 0 ? $totalRemaining : $availableCourse->remaining_capacity;

            return [
                'id'                 => $availableCourse->course->id,
                'name'               => $availableCourse->course->name,
                'code'               => $availableCourse->course->code,
                'groups'             => $groups,
                'credit_hours'       => $availableCourse->course->credit_hours,
                'available_course_id'=> $availableCourse->id,
                'remaining_capacity' => $remainingCapacityValue,
            ];
        });
        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    /**
     * Get the schedules for a specific available course using the service.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function schedules($id): JsonResponse
    {
        try {
            $group = request()->input('group');
            // If exception flag is provided and truthy, ignore group filtering by passing null
            $exception = request()->boolean('exceptionForDifferentLevels', false);
            if ($exception) {
                $group = null;
            }
            $schedules = $this->availableCourseService->getSchedules($id, $group);
            return successResponse('Schedules fetched successfully.', $schedules);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@schedules', $e, ['id' => $id]);
            return errorResponse('Failed to fetch schedules.', [], 500);
        }
    }

    /**
     * Get the eligibilities for a specific available course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function eligibilities($id): JsonResponse
    {
        try {
            $eligibilities = $this->availableCourseService->getEligibilities($id);
            return successResponse('Eligibilities fetched successfully.', $eligibilities);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@eligibilities', $e, ['id' => $id]);
            return errorResponse('Failed to fetch eligibilities.', [], 500);
        }
    }

    /**
     * Get available course statistics for stat2 cards.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->availableCourseService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('AvailableCourseController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 