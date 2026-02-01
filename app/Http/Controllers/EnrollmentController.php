<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\View\View;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\EnrollmentGuidingService;
use App\Services\CreditHoursExceptionService;
use App\Models\Enrollment;
use App\Rules\AcademicAdvisorAccessRule;
use App\Exceptions\BusinessValidationException;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'enrollments' => 'required|array',
            'enrollments.*.available_course_id' => 'required|exists:available_courses,id',
            'enrollments.*.selected_schedule_ids' => 'required|array',
            'enrollments.*.selected_schedule_ids.*' => 'exists:available_course_schedules,id',
            'enrollments.*.create_schedule' => 'required|in:true,false',
        ]);

        try {
            $validated = $request->all();
            $results = $this->enrollmentService->create($validated);
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
            $enrollment->load('schedules');
            $this->enrollmentService->deleteEnrollment($enrollment);
            return successResponse('Enrollment deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
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
     * Show the legacy/grade-only add enrollment page.
     *
     * @return View
     */
    public function addOld(): View
    {
        return view('enrollment.old-enrollment-add');
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
     * Start an async enrollments import.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:51200',
                'template_select' => 'required|in:system,sis',
            ]);

            $result = $this->enrollmentService->import($validated);

            return successResponse(__('Import initiated successfully.'), $result);
        } catch (Exception $e) {
            logError('EnrollmentController@import', $e, ['request' => $request->all()]);
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
            $status = $this->enrollmentService->getImportStatus($uuid);

            if (!$status) {
                return errorResponse(__('Import not found.'), [], 404);
            }

            return successResponse(__('Import status retrieved successfully.'), $status);
        } catch (Exception $e) {
            logError('EnrollmentController@importStatus', $e, ['uuid' => $uuid]);
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
            $result = $this->enrollmentService->cancelImport($uuid);
            return successResponse(__('Import cancelled successfully.'), $result);
        } catch (Exception $e) {
            logError('EnrollmentController@importCancel', $e, ['uuid' => $uuid]);
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
        return $this->enrollmentService->downloadImport($uuid);
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
     * Start an async enrollments export.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
        ]);

        try {
            $validated = $request->all();
            $result = $this->enrollmentService->exportEnrollments($validated);

            return successResponse(__('Export initiated successfully.'), $result);
        } catch (Exception $e) {
            logError('EnrollmentController@export', $e, ['request' => $request->all()]);
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
            $status = $this->enrollmentService->getExportStatus($uuid);

            if (!$status) {
                return errorResponse(__('Export not found.'), [], 404);
            }

            return successResponse(__('Export status retrieved successfully.'), $status);
        } catch (Exception $e) {
            logError('EnrollmentController@exportStatus', $e, ['uuid' => $uuid]);
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
            $result = $this->enrollmentService->cancelExport($uuid);
            return successResponse(__('Export cancelled successfully.'), $result);
        } catch (Exception $e) {
            logError('EnrollmentController@exportCancel', $e, ['uuid' => $uuid]);
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
        return $this->enrollmentService->downloadExport($uuid);
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
        // term_id is required for both individual and group exports
        $request->validate([
            'academic_id' => 'nullable|string',
            'national_id' => 'nullable|string',
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        try {
            $filters = $request->only(['academic_id', 'national_id', 'program_id', 'level_id', 'term_id']);

            // Determine mode: individual if academic_id or national_id provided; otherwise group
            $isIndividual = !empty($filters['academic_id']) || !empty($filters['national_id']);

            if ($isIndividual) {
                // ensure at least one identifier provided (already true by $isIndividual), nothing else required
            } else {
                // group export: require program_id and level_id
                if (empty($filters['program_id']) || empty($filters['level_id'])) {
                    return errorResponse('For group export please provide both program_id and level_id along with term_id.', [], 422);
                }
            }

            /** @var \App\Services\EnrollmentDocumentService $documentService */
            $documentService = app(\App\Services\EnrollmentDocumentService::class);

            if ($isIndividual) {
                // Individual: generate a single PDF for the specific student and return it (no ZIP)
                // Resolve the student via academic_id or national_id
                $student = null;
                if (!empty($filters['academic_id'])) {
                    $student = \App\Models\Student::where('academic_id', $filters['academic_id'])->first();
                }
                if (!$student && !empty($filters['national_id'])) {
                    $student = \App\Models\Student::where('national_id', $filters['national_id'])->first();
                }

                if (!$student) {
                    return errorResponse('Student not found with the provided identifier.', [], 404);
                }

                $pdfResult = $documentService->generatePdf($student, $filters['term_id']);
                // $pdfResult contains ['url' => '/storage/documents/enrollments/pdf/...', 'filename' => '...']
                $publicPath = parse_url($pdfResult['url'], PHP_URL_PATH);
                $storagePath = public_path(ltrim($publicPath, '/'));

                if (!file_exists($storagePath)) {
                    return errorResponse('Generated PDF not found.', [], 500);
                }

                return response()->download($storagePath, $pdfResult['filename'])->deleteFileAfterSend(false);
            }

            // Group: generate PDFs for matching students and return ZIP
            $result = $documentService->exportDocumentsByFilters($filters);
            return response()->download($result['temp_zip'], $result['zip_name'])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            logError('EnrollmentController@exportDocuments', $e, ['request' => $request->all()]);
            return errorResponse($e->getMessage() ?: 'Internal server error.', [], 500);
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

    public function getEnrollmentsByStudent(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        try {
            $enrollments = $this->enrollmentService->getEnrollmentsByStudent(
                $request->student_id,
            );
            return successResponse('Enrollments fetched successfully.', $enrollments);
        } catch (Exception $e) {
            logError('EnrollmentController@getEnrollmentsByStudent', $e);
            return errorResponse('Failed to get student enrollments.', [], 500);
        }
    }

    /**
     * Get enrollment guide for a student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGuiding(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        try {
            $guidingService = new EnrollmentGuidingService($request->student_id, $request->term_id);
            $guide = $guidingService->guide();
            return successResponse('Enrollment guide fetched successfully.', $guide);
        } catch (Exception $e) {
            logError('EnrollmentController@getGuiding', $e, ['student_id' => $request->student_id]);
            return errorResponse('Failed to fetch enrollment guide.', [], 500);
        }
    }
}
