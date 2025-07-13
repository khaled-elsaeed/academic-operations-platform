<?php

namespace App\Services;

use App\Models\Faculty;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class FacultyService
{
    /**
     * Get faculty statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalFaculties = Faculty::count();
        $facultiesWithPrograms = Faculty::has('programs')->count();
        $facultiesWithoutPrograms = Faculty::doesntHave('programs')->count();

        return [
            'total' => [
                'total' => $totalFaculties,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'withPrograms' => [
                'total' => $facultiesWithPrograms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'withoutPrograms' => [
                'total' => $facultiesWithoutPrograms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get faculty data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $faculties = Faculty::with('programs');

        return DataTables::of($faculties)
            ->addColumn('programs_count', function ($faculty) {
                return $faculty->programs->count();
            })
            ->addColumn('action', function ($faculty) {
                return $this->renderActionButtons($faculty);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Faculty $faculty
     * @return string
     */
    protected function renderActionButtons($faculty): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item editFacultyBtn" href="javascript:void(0);" data-id="' . $faculty->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteFacultyBtn" href="javascript:void(0);" data-id="' . $faculty->id . '">
                        <i class="bx bx-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        ';
    }

    /**
     * Create a new faculty.
     *
     * @param array $data
     * @return Faculty
     */
    public function createFaculty(array $data): Faculty
    {
        return Faculty::create([
            'name' => $data['name']
        ]);
    }

    /**
     * Get faculty details.
     *
     * @param Faculty $faculty
     * @return Faculty
     */
    public function getFaculty(Faculty $faculty): Faculty
    {
        return $faculty->load('programs');
    }

    /**
     * Update an existing faculty.
     *
     * @param Faculty $faculty
     * @param array $data
     * @return Faculty
     */
    public function updateFaculty(Faculty $faculty, array $data): Faculty
    {
        $faculty->update([
            'name' => $data['name']
        ]);

        return $faculty;
    }

    /**
     * Delete a faculty.
     *
     * @param Faculty $faculty
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteFaculty(Faculty $faculty): void
    {
        // Check if faculty has programs
        if ($faculty->programs()->count() > 0) {
            throw new BusinessValidationException('Cannot delete faculty that has programs assigned.');
        }

        $faculty->delete();
    }
} 