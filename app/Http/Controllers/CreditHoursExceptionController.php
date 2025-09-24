<?php

namespace App\Http\Controllers;

use App\Models\CreditHoursException;
use App\Models\Student;
use App\Models\Term;
use App\Services\CreditHoursExceptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Exception;
use App\Exceptions\BusinessValidationException;
use Maatwebsite\Excel\Facades\Excel;

class CreditHoursExceptionController extends Controller
{
    /**
     * CreditHoursExceptionController constructor.
     *
     * @param CreditHoursExceptionService $exceptionService
     */
    public function __construct(protected CreditHoursExceptionService $exceptionService)
    {}

    /**
     * Display the credit hours exceptions index page.
     */
    public function index(): View
    {
        return view('credit_hours_exceptions.index');
    }

    /**
     * Store a new credit hours exception.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'term_id' => 'required|exists:terms,id',
            'additional_hours' => 'required|integer|min:1|max:12',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $exception = $this->exceptionService->createException(
                $request->all(),
                auth()->user()
            );

            return successResponse('Credit hours exception created successfully.', $exception);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update an existing credit hours exception.
     */
    public function update(Request $request, CreditHoursException $exception): JsonResponse
    {
        $request->validate([
            'additional_hours' => 'required|integer|min:1|max:12',
            'reason' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $exception = $this->exceptionService->updateException($exception, $request->all());
            return successResponse('Credit hours exception updated successfully.', $exception);
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@update', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Deactivate a credit hours exception.
     */
    public function deactivate(CreditHoursException $exception): JsonResponse
    {
        try {
            $this->exceptionService->deactivateException($exception);
            return successResponse('Credit hours exception deactivated successfully.');
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@deactivate', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Activate a credit hours exception.
     */
    public function activate(CreditHoursException $exception): JsonResponse
    {
        try {
            $this->exceptionService->activateException($exception);
            return successResponse('Credit hours exception activated successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@activate', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Delete a credit hours exception.
     */
    public function destroy(CreditHoursException $exception): JsonResponse
    {
        try {
            $exception->delete();
            return successResponse('Credit hours exception deleted successfully.');
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@destroy', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get the datatable for credit hours exceptions.
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->exceptionService->getDatatable();
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get students for dropdown.
     */
    public function getStudents(): JsonResponse
    {
        try {
            $students = Student::select('id', 'name_en', 'academic_id')
                ->orderBy('name_en')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'text' => "{$student->name_en} ({$student->academic_id})"
                    ];
                });

            return successResponse('Students retrieved successfully.', $students);
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@getStudents', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get terms for dropdown.
     */
    public function getTerms(): JsonResponse
    {
        try {
            $terms = Term::select('id', 'season', 'year', 'is_active')
                ->where('is_active', true)
                ->orderBy('year', 'desc')
                ->orderBy('season')
                ->get()
                ->map(function ($term) {
                    return [
                        'id' => $term->id,
                        'text' => "{$term->season} {$term->year}"
                    ];
                });

            return successResponse('Terms retrieved successfully.', $terms);
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@getTerms', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get exception details for editing.
     */
    public function show(CreditHoursException $exception): JsonResponse
    {
        try {
            $exception->load(['student:id,name_en,academic_id', 'term:id,season,year', 'grantedBy:id,first_name,last_name']);
            return successResponse('Exception details retrieved successfully.', $exception);
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@show', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get statistics for credit hours exceptions.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->exceptionService->getStats();
            return successResponse('Statistics retrieved successfully.', $stats);
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Import credit hours exceptions from uploaded file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'exceptions_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $result = $this->exceptionService->importExceptions($request->file('exceptions_file'));
            return successResponse($result['message'], [
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors']
            ]);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], 422);
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@import', $e, ['request' => $request->all()]);
            return errorResponse('Failed to import credit hours exceptions.', [], 500);
        }
    }

    /**
     * Download the import template for credit hours exceptions.
     */
    public function downloadTemplate()
    {
        try {
            return $this->exceptionService->downloadTemplate();
        } catch (Exception $e) {
            logError('CreditHoursExceptionController@downloadTemplate', $e);
            return errorResponse('Failed to download template.', [], 500);
        }
    }
} 