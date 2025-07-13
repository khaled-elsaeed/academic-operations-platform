<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\TermService;
use Exception;
use App\Exceptions\BusinessValidationException;

class TermController extends Controller
{
    /**
     * TermController constructor.
     *
     * @param TermService $termService
     */
    public function __construct(protected TermService $termService)
    {}

    /**
     * Display the term management page
     */
    public function index(): View
    {
        return view('term.index');
    }

    /**
     * Get term statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->termService->getStats();
            return successResponse('Stats fetched successfully.', $stats);
        } catch (Exception $e) {
            logError('TermController@stats', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get term data for DataTables
     */
    public function datatable(): JsonResponse
    {
        try {
            return $this->termService->getDatatable();
        } catch (Exception $e) {
            logError('TermController@datatable', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Store a newly created term
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'season' => 'required|string|in:Fall,Spring,Summer',
            'year' => 'required|string',
            'code' => 'required|string|max:10|unique:terms,code',
            'is_active' => 'boolean'
        ]);

        try {
            $validated = $request->all();
            $term = $this->termService->createTerm($validated);
            return successResponse('Term created successfully.', $term);
        } catch (Exception $e) {
            logError('TermController@store', $e, ['request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Display the specified term
     */
    public function show(Term $term): JsonResponse
    {
        try {
            $term = $this->termService->getTerm($term);
            return successResponse('Term details fetched successfully.', $term);
        } catch (Exception $e) {
            logError('TermController@show', $e, ['term_id' => $term->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Update the specified term
     */
    public function update(Request $request, Term $term): JsonResponse
    {
        $request->validate([
            'season' => 'required|string|in:Fall,Spring,Summer',
            'year' => 'required|string',
            'code' => 'required|string|max:10|unique:terms,code,' . $term->id,
            'is_active' => 'boolean'
        ]);

        try {
            $validated = $request->all();
            $term = $this->termService->updateTerm($term, $validated);
            return successResponse('Term updated successfully.', $term);
        } catch (Exception $e) {
            logError('TermController@update', $e, ['term_id' => $term->id, 'request' => $request->all()]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Remove the specified term
     */
    public function destroy(Term $term): JsonResponse
    {
        try {
            $this->termService->deleteTerm($term);
            return successResponse('Term deleted successfully.');
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            logError('TermController@destroy', $e, ['term_id' => $term->id]);
            return errorResponse('Internal server error.', [], 500);
        }
    }

    /**
     * Get all terms (for dropdown and forms)
     */
    public function all(): JsonResponse
    {
        try {
            $terms = $this->termService->getAll();
            return successResponse('Terms fetched successfully.', $terms);
        } catch (Exception $e) {
            logError('TermController@all', $e);
            return errorResponse('Internal server error.', [], 500);
        }
    }
} 