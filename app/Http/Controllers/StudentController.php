<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request,JsonResponse};
use Illuminate\View\View;
use App\Services\StudentService;
use App\Models\Student;
use App\Http\Requests\{StoreStudentRequest,UpdateStudentRequest};
use App\Exceptions\BusinessValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    /**
     * StudentController constructor.
     *
     * @param StudentService $studentService
     */
    public function __construct(protected StudentService $studentService)
    {}

    /**
     * Display the students page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('student.index');
    }

    /**
     * Return data for DataTable AJAX requests.
     *
     * @return JsonResponse
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->studentService->getDatatable();
        } catch (Exception $e) {
            logError('StudentController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get student statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->studentService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('StudentController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified student.
     *
     * @param string $identifier
     * @return JsonResponse
     */
    public function show(string $identifier): JsonResponse
    {
        try {
            $studentData = $this->studentService->getStudent($identifier);
            return successResponse('Student details fetched successfully.', $studentData);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (ModelNotFoundException $e) {
            return errorResponse('Student not found.', [], 404);
        } catch (Exception $e) {
            logError('StudentController@show', $e, ['identifier' => $identifier]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a new student.
     *
     * @param StoreStudentRequest $request
     * @return JsonResponse
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = $this->studentService->createStudent($validated);
            return successResponse('Student created successfully.', $student);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('StudentController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update the specified student.
     *
     * @param UpdateStudentRequest $request
     * @param Student $student
     * @return JsonResponse
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = $this->studentService->updateStudent($student, $validated);
            return successResponse('Student updated successfully.', $student);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('StudentController@update', $e, ['student_id' => $student->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete a student.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function destroy(Student $student): JsonResponse
    {
        try {
            $this->studentService->deleteStudent($student);
            return successResponse('Student deleted successfully.');
        } catch (Exception $e) {
            logError('StudentController@destroy', $e, ['student_id' => $student->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }


    /**
     * Start an async students import.
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

            $result = $this->studentService->import($validated);

            return successResponse(__('Import initiated successfully.'), $result);
        } catch (Exception $e) {
            logError('StudentController@import', $e, ['request' => $request->all()]);
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
            $status = $this->studentService->getImportStatus($uuid);

            if (!$status) {
                return errorResponse(__('Import not found.'), [], 404);
            }

            return successResponse(__('Import status retrieved successfully.'), $status);
        } catch (Exception $e) {
            logError('StudentController@importStatus', $e, ['uuid' => $uuid]);
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
            $result = $this->studentService->cancelImport($uuid);
            return successResponse(__('Import cancelled successfully.'), $result);
        } catch (Exception $e) {
            logError('StudentController@importCancel', $e, ['uuid' => $uuid]);
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
        return $this->studentService->downloadImport($uuid);
    }

    /**
     * Download the students import template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function downloadTemplate()
    {
        try {
            return $this->studentService->downloadTemplate();
        } catch (Exception $e) {
            logError('StudentController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', [], 500);
        }
    }

    /**
     * Start an async students export.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'nullable|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
        ]);

        try {
            $validated = $request->all();
            $result = $this->studentService->exportStudentsAsync($validated);

            return successResponse(__('Export initiated successfully.'), $result);
        } catch (Exception $e) {
            logError('StudentController@export', $e, ['request' => $request->all()]);
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
            $status = $this->studentService->getExportStatus($uuid);

            if (!$status) {
                return errorResponse(__('Export not found.'), [], 404);
            }

            return successResponse(__('Export status retrieved successfully.'), $status);
        } catch (Exception $e) {
            logError('StudentController@exportStatus', $e, ['uuid' => $uuid]);
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
            $result = $this->studentService->cancelExport($uuid);
            return successResponse(__('Export cancelled successfully.'), $result);
        } catch (Exception $e) {
            logError('StudentController@exportCancel', $e, ['uuid' => $uuid]);
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
        return $this->studentService->downloadExport($uuid);
    }

    /**
     * Download enrollment document as PDF.
     *
     * @param int $studentId
     * @return JsonResponse
     */
    public function downloadPdf(int $studentId): JsonResponse
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadEnrollmentDocument($studentId, $termId,'pdf');
            $data = $serviceResponse;
            return response()->json(['success' => true, 'url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadPdf', $e, ['student_id' => $studentId]);
            return errorResponse('Failed to generate PDF.', [], 500);
        }
    }

    /**
     * Download enrollment document as Word.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function downloadWord(Student $student): JsonResponse
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadEnrollmentDocument($student, $termId,'word');
            $data = $serviceResponse;
            return response()->json(['success' => true, 'url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadWord', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate Word document.', [], 500);
        }
    }

    /**
     * Download timetable as a server-generated PDF.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function downloadTimetable(Student $student): JsonResponse
    {
        try {
            $termId = request()->query('term_id');
            $serviceResponse = $this->studentService->downloadTimetableDocument($student, $termId);
            $data = $serviceResponse;
            return response()->json(['url' => $data['url'] ?? null]);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Exception $e) {
            logError('StudentController@downloadTimetable', $e, ['student_id' => $student->id]);
            return errorResponse('Failed to generate timetable PDF.', [], 500);
        }
    }
} 