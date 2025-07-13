<?php

namespace App\Services;

use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class TermService
{
    /**
     * Get term statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalTerms = Term::count();
        $activeTerms = Term::where('is_active', true)->count();
        $inactiveTerms = Term::where('is_active', false)->count();
        $currentYear = date('Y');
        $currentYearTerms = Term::where('year', $currentYear)->count();

        return [
            'total' => [
                'total' => $totalTerms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'active' => [
                'total' => $activeTerms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'inactive' => [
                'total' => $inactiveTerms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'currentYear' => [
                'total' => $currentYearTerms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get term data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $terms = Term::with('enrollments');

        return DataTables::of($terms)
            ->addColumn('name', function ($term) {
                return $term->name;
            })
            ->addColumn('enrollments_count', function ($term) {
                return $term->enrollments->count();
            })
            ->addColumn('status', function ($term) {
                return $term->is_active 
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($term) {
                return $this->renderActionButtons($term);
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Term $term
     * @return string
     */
    protected function renderActionButtons($term): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item editTermBtn" href="javascript:void(0);" data-id="' . $term->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteTermBtn" href="javascript:void(0);" data-id="' . $term->id . '">
                        <i class="bx bx-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        ';
    }

    /**
     * Create a new term.
     *
     * @param array $data
     * @return Term
     */
    public function createTerm(array $data): Term
    {
        return Term::create([
            'season' => $data['season'],
            'year' => $data['year'],
            'code' => $data['code'],
            'is_active' => $data['is_active'] ?? false
        ]);
    }

    /**
     * Get term details.
     *
     * @param Term $term
     * @return Term
     */
    public function getTerm(Term $term): Term
    {
        return $term->load('enrollments');
    }

    /**
     * Update an existing term.
     *
     * @param Term $term
     * @param array $data
     * @return Term
     */
    public function updateTerm(Term $term, array $data): Term
    {
        $term->update([
            'season' => $data['season'],
            'year' => $data['year'],
            'code' => $data['code'],
            'is_active' => $data['is_active'] ?? false
        ]);

        return $term;
    }

    /**
     * Delete a term.
     *
     * @param Term $term
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteTerm(Term $term): void
    {
        // Check if term has enrollments
        if ($term->enrollments()->count() > 0) {
            throw new BusinessValidationException('Cannot delete term that has enrollments assigned.');
        }

        $term->delete();
    }

    /**
     * Get all terms (for dropdown and forms).
     *
     * @return array
     */
    public function getAll(): array
    {
        return Term::orderBy('year', 'desc')->orderBy('season')->get()->map(function ($term) {
            return [
                'id' => $term->id,
                'name' => $term->name,
                'season' => $term->season,
                'year' => $term->year,
                'code' => $term->code,
                'is_active' => (bool) $term->is_active,
            ];
        })->toArray();
    }
} 