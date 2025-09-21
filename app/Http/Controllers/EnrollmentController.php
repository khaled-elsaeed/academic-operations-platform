<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\Enrollment\EnrollmentService;
use App\Services\CreditHoursExceptionService;
use App\Models\Enrollment;
use App\Rules\AcademicAdvisorAccessRule;
use App\Exceptions\BusinessValidationException;
use Exception;

class EnrollmentController extends Controller
{
    /**
     * EnrollmentController constructor.
     *
     * @param EnrollmentService $enrollmentService
     */
    public function __construct(protected EnrollmentService $enrollmentService, protected CreditHoursExceptionService $creditHoursExceptionService)
    {}

    /**
     * Display the enrollment index page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('enrollment.index');
    }

    /**
     * Get datatable data for enrollments.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->enrollmentService->getDatatable();
        } catch (Exception $e) {
            logError('EnrollmentController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get enrollment statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->enrollmentService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('EnrollmentController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store new enrollments for a student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'term_id' => 'required|exists:terms,id',
            'available_course_ids' => 'required|array|min:1',
            'available_course_ids.*' => 'exists:available_courses,id',
            'available_course_schedule_ids' => 'array',
            'available_course_schedule_ids.*' => 'exists:available_course_schedules,id',
            'course_schedule_mapping' => 'array',
        ]);

        try {
            $validated = $request->all();
            $results = $this->enrollmentService->createEnrollments($validated);
            return successResponse('Enrollments created successfully.', $results);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EnrollmentController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete an enrollment.
     *
     * @param Enrollment $enrollment
     * @return JsonResponse
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        try {
            $this->enrollmentService->deleteEnrollment($enrollment);
            return successResponse('Enrollment deleted successfully.');
        } catch (Exception $e) {
            logError('EnrollmentController@destroy', $e, ['enrollment_id' => $enrollment->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Show the add enrollment page.
     *
     * @return View
     */
    public function add(): View
    {
        return view('enrollment.add');
    }

    /**
     * Find a student by national or academic ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function findStudent(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        try {
            $student = $this->enrollmentService->findStudent($request->identifier);
            return successResponse('Student found successfully.', $student);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('EnrollmentController@findStudent', $e, ['identifier' => $request->identifier]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get available courses for a student and term.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableCourses(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
            'term_id' => 'required|exists:terms,id',
        ]);

        try {
            $availableCourses = $this->enrollmentService->getAvailableCourses($request->student_id, $request->term_id);
            return successResponse('Available courses fetched successfully.', $availableCourses);
        } catch (Exception $e) {
            logError('EnrollmentController@availableCourses', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all enrollments for a student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function studentEnrollments(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id', new AcademicAdvisorAccessRule()],
        ]);

        try {
            $enrollments = $this->enrollmentService->getStudentEnrollments($request->student_id);
            return successResponse('Student enrollments fetched successfully.', $enrollments);
        } catch (Exception $e) {
            logError('EnrollmentController@studentEnrollments', $e, ['student_id' => $request->student_id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Import enrollments from an uploaded file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'enrollments_file' => 'required|file|mimes:xlsx,xls'
        ]);
        try {
            $result = $this->enrollmentService->importEnrollments($request->file('enrollments_file'));
            return successResponse($result['message'], [
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors']
            ]);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('EnrollmentController@import', $e, ['request' => $request->all()]);
            return errorResponse('Failed to import enrollments.', [], 500);
        }
    }

    /**
     * Download the enrollments import template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function downloadTemplate()
    {
        try {
            return $this->enrollmentService->downloadTemplate();
        } catch (Exception $e) {
            logError('EnrollmentController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', [], 500);
        }
    }

    /**
     * Export enrollments for a selected academic term.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function export(Request $request)
    {
        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
        ]);

        $termId = $request->input('term_id');
        $programId = $request->input('program_id');
        $levelId = $request->input('level_id');

        return $this->enrollmentService->exportEnrollments($termId, $programId, $levelId);
    }

    /**
     * Show the page for exporting enrollment documents (batch).
     *
     * @return View
     */
    public function exportDocumentsPage(): View
    {
        return view('enrollment.export_documents');
    }

    /**
     * Export enrollment documents for students matching filters.
     * Accepts: academic_id, national_id, program_id, level_id
     * Returns: ZIP file of generated PDFs.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function exportDocuments(Request $request)
    {
        $request->validate([
            'academic_id' => 'nullable|string',
            'national_id' => 'nullable|string',
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
            'select_all_programs' => 'nullable|boolean',
        ]);

        try {
            $query = \App\Models\Student::query();

            if ($request->filled('academic_id')) {
                $query->where('academic_id', $request->academic_id);
            }

            if ($request->filled('national_id')) {
                $query->where('national_id', $request->national_id);
            }

            if ($request->filled('program_id')) {
                $query->where('program_id', $request->program_id);
            }

            if ($request->filled('level_id')) {
                $query->where('level_id', $request->level_id);
            }

            // If no individual identifier provided and select_all_programs not set,
            // we will use program/level filters; otherwise if nothing given, return error.
            $students = $query->get();

            if ($students->isEmpty()) {
                return errorResponse('No students found matching the provided filters.', [], 404);
            }

            $termId = $request->input('term_id');

            $documentService = app(\App\Services\EnrollmentDocumentService::class);

            // Temporary files for zipping
            $files = [];

            foreach ($students as $student) {
                try {
                    $result = $documentService->generatePdf($student, $termId);
                    $publicPath = parse_url($result['url'], PHP_URL_PATH);
                    $storagePath = public_path(ltrim($publicPath, '/'));
                    if (file_exists($storagePath)) {
                        $files[$result['filename']] = $storagePath;
                    }
                } catch (Exception $e) {
                    // skip student if generation failed
                    \Log::error('exportDocuments: failed for student ' . $student->id, ['error' => $e->getMessage()]);
                }
            }

            if (empty($files)) {
                return errorResponse('Failed to generate any documents.', [], 500);
            }

            $zipName = 'enrollment_documents_' . now()->format('Ymd_His') . '.zip';
            $tempZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

            $zip = new \ZipArchive();
            if ($zip->open($tempZip, \ZipArchive::CREATE) !== true) {
                return errorResponse('Failed to create zip archive.', [], 500);
            }

            foreach ($files as $name => $path) {
                $zip->addFile($path, $name);
            }

            $zip->close();

            return response()->download($tempZip, $zipName)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            logError('EnrollmentController@exportDocuments', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
    * Get remaining credit hours for a student in a specific term.
    *
    * @param  Request  $request
    * @return JsonResponse
    */
    public function getRemainingCreditHours(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'term_id'    => ['required', 'exists:terms,id'],
            ]);

            $result = $this->enrollmentService->getRemainingCreditHoursForStudent(
                $validated['student_id'],
                $validated['term_id']
            );
            return successResponse('Remaining credit hours fetched successfully.', $result);
        } catch (Exception $e) {
            logError('EnrollmentController@getRemainingCreditHours', $e);
            return errorResponse('Failed to get remaining credit hours.', [], 500);
        }
    }

    public function getSchedules(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id'    => ['required', 'exists:terms,id'],
        ]);

        try {
            $schedules = $this->enrollmentService->getSchedules(
                $request->student_id,
                $request->term_id
            );
            return successResponse('Schedules fetched successfully.', $schedules);
        } catch (Exception $e) {
            logError('EnrollmentController@getSchedules', $e);
            return errorResponse('Failed to get student schedules.', [], 500);
        }
    }
}