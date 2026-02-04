<?php

namespace App\Http\Controllers\AvailableCourse;

use App\Http\Controllers\Controller;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\AvailableCourse\AvailableCourseService;
use App\Exports\AvailableCoursesTemplateExport;
use App\Http\Requests\{StoreAvailableCourseRequest};
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
            $availableCourse = $this->availableCourseService->create($validated);
            return successResponse('Available course created successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get available courses for a student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableCoursesByStudent(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        try {
            $exception = $request->boolean('exceptionForDifferentLevels', false);
            $availableCourses = $this->availableCourseService->getAvailableCoursesByStudent($request->student_id, $request->term_id, $exception);
            return successResponse('Available courses fetched successfully.', $availableCourses);
        } catch (Exception $e) {
            logError('AvailableCourseController@availableCoursesByStudent', $e, ['request' => $request->all()]);
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
            $availableCourse = AvailableCourse::findOrFail($id);
            $this->availableCourseService->delete($availableCourse);
            return successResponse('Available course deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@destroy', $e, ['id' => $id]);
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
        $availableCourse = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level', 'schedules.scheduleAssignments.scheduleSlot'])
            ->findOrFail($id);
        return view('available_course.edit', compact('availableCourse'));
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

    /**
     * Update basic information of available course.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateBasic(Request $request, $id): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        try {
            $availableCourse = $this->availableCourseService->updateAvailableCourse($id, $request->only(['course_id', 'term_id']));
            return successResponse('Available course updated successfully.', $availableCourse);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('AvailableCourseController@updateBasic', $e, ['id' => $id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get programs for a specific available course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function programs($id): JsonResponse
    {
        try {
            $programs = $this->availableCourseService->getPrograms($id);
            return successResponse('Programs fetched successfully.', $programs);
        } catch (Exception $e) {
            logError('AvailableCourseController@programs', $e, ['id' => $id]);
            return errorResponse('Failed to fetch programs.', [], 500);
        }
    }

    /**
     * Get levels for a specific available course.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function levels($id): JsonResponse
    {
        try {
            $levels = $this->availableCourseService->getLevels($id);
            return successResponse('Levels fetched successfully.', $levels);
        } catch (Exception $e) {
            logError('AvailableCourseController@levels', $e, ['id' => $id]);
            return errorResponse('Failed to fetch levels.', [], 500);
        }
    }

    /**
     * Start an async available course import.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            ]);

            $result = $this->availableCourseService->import($validated);

            return successResponse(__('Import initiated successfully.'), $result);
        } catch (Exception $e) {
            logError('AvailableCourseController@import', $e, ['request' => $request->all()]);
            return errorResponse(__('Failed to initiate import.'), [], 500);
        }
    }

    /**
     * Get import status by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function importStatus(string $uuid): JsonResponse
    {
        try {
            $status = $this->availableCourseService->getImportStatus($uuid);

            if (!$status) {
                return errorResponse(__('Import not found.'), [], 404);
            }

            return successResponse(__('Import status retrieved successfully.'), $status);
        } catch (Exception $e) {
            logError('AvailableCourseController@importStatus', $e, ['uuid' => $uuid]);
            return errorResponse(__('Failed to retrieve import status.'), [], 500);
        }
    }

    /**
     * Cancel import task by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function importCancel(string $uuid): JsonResponse
    {
        try {
            $result = $this->availableCourseService->cancelImport($uuid);
            return successResponse(__('Import cancelled successfully.'), $result);
        } catch (Exception $e) {
            logError('AvailableCourseController@importCancel', $e, ['uuid' => $uuid]);
            return errorResponse(__('Failed to cancel import.'), [], 500);
        }
    }

    /**
     * Download completed import report by UUID.
     *
     * @param string $uuid
     * @return BinaryFileResponse|JsonResponse
     */
    public function importDownload(string $uuid): BinaryFileResponse|JsonResponse
    {
        return $this->availableCourseService->downloadImport($uuid);
    }

    /**
     * Start an async available courses export.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
        ]);

        try {
            $validated = $request->all();
            $result = $this->availableCourseService->exportAvailableCourses($validated);

            return successResponse(__('Export initiated successfully.'), $result);
        } catch (Exception $e) {
            logError('AvailableCourseController@export', $e, ['request' => $request->all()]);
            return errorResponse(__('Failed to initiate export.'), [], 500);
        }
    }

    /**
     * Get export status by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function exportStatus(string $uuid): JsonResponse
    {
        try {
            $status = $this->availableCourseService->getExportStatus($uuid);

            if (!$status) {
                return errorResponse(__('Export not found.'), [], 404);
            }

            return successResponse(__('Export status retrieved successfully.'), $status);
        } catch (Exception $e) {
            logError('AvailableCourseController@exportStatus', $e, ['uuid' => $uuid]);
            return errorResponse(__('Failed to retrieve export status.'), [], 500);
        }
    }

    /**
     * Cancel export task by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function exportCancel(string $uuid): JsonResponse
    {
        try {
            $result = $this->availableCourseService->cancelExport($uuid);
            return successResponse(__('Export cancelled successfully.'), $result);
        } catch (Exception $e) {
            logError('AvailableCourseController@exportCancel', $e, ['uuid' => $uuid]);
            return errorResponse(__('Failed to cancel export.'), [], 500);
        }
    }

    /**
     * Download completed export file by UUID.
     *
     * @param string $uuid
     * @return BinaryFileResponse|JsonResponse
     */
    public function exportDownload(string $uuid): BinaryFileResponse|JsonResponse
    {
        return $this->availableCourseService->downloadExport($uuid);
    }
}