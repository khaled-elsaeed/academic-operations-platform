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

        $lastUpdateTime = formatDate(Term::max('updated_at'));

        return [
            'total' => [
                'total' => $totalTerms,
                'lastUpdateTime' => $lastUpdateTime
            ],
            'active' => [
                'total' => $activeTerms,
                'lastUpdateTime' => $lastUpdateTime
            ],
            'inactive' => [
                'total' => $inactiveTerms,
                'lastUpdateTime' => $lastUpdateTime
            ],

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

        // Apply search filters
        $terms = $this->applySearchFilters($terms);

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
     * Apply search filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySearchFilters($query)
    {
        // Season filter
        if (request()->filled('search_season')) {
            $query->where('season', request('search_season'));
        }

        // Year filter (supports academic year format like 2015-2016)
        if (request()->filled('search_year')) {
            $searchYear = request('search_year');
            // Check if it's in academic year format (e.g., 2015-2016)
            if (strpos($searchYear, '-') !== false) {
                $years = explode('-', $searchYear);
                if (count($years) == 2) {
                    $query->where('year', $searchYear);
                }
            } else {
                // Single year search
                $query->where('year', 'LIKE', '%' . $searchYear . '%');
            }
        }

        // Code filter
        if (request()->filled('search_code')) {
            $query->where('code', 'LIKE', '%' . request('search_code') . '%');
        }

        // Active status filter
        if (request()->filled('search_active')) {
            $query->where('is_active', request('search_active'));
        }

        return $query;
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
        return Term::where('is_active', true)
            ->orderBy('year', 'desc')
            ->orderBy('season')
            ->get()
            ->map(function ($term) {
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