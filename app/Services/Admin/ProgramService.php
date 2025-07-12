<?php

namespace App\Services\Admin;

use App\Models\Program;
use App\Models\Faculty;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class ProgramService
{
    /**
     * Get program statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalPrograms = Program::count();
        $programsWithStudents = Program::has('students')->count();
        $programsWithoutStudents = Program::doesntHave('students')->count();

        return [
            'total' => [
                'total' => $totalPrograms,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'withStudents' => [
                'total' => $programsWithStudents,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ],
            'withoutStudents' => [
                'total' => $programsWithoutStudents,
                'lastUpdateTime' => formatDate(now(), 'Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Get program data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $programs = Program::with(['faculty', 'students']);

        return DataTables::of($programs)
            ->addColumn('faculty_name', function ($program) {
                return $program->faculty ? $program->faculty->name : 'N/A';
            })
            ->addColumn('students_count', function ($program) {
                return $program->students->count();
            })
            ->addColumn('action', function ($program) {
                return $this->renderActionButtons($program);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Program $program
     * @return string
     */
    protected function renderActionButtons($program): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item editProgramBtn" href="javascript:void(0);" data-id="' . $program->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteProgramBtn" href="javascript:void(0);" data-id="' . $program->id . '">
                        <i class="bx bx-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        ';
    }

    /**
     * Create a new program.
     *
     * @param array $data
     * @return Program
     */
    public function createProgram(array $data): Program
    {
        return Program::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'faculty_id' => $data['faculty_id']
        ]);
    }

    /**
     * Get program details.
     *
     * @param Program $program
     * @return Program
     */
    public function getProgram(Program $program): Program
    {
        return $program->load(['faculty', 'students']);
    }

    /**
     * Update an existing program.
     *
     * @param Program $program
     * @param array $data
     * @return Program
     */
    public function updateProgram(Program $program, array $data): Program
    {
        $program->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'faculty_id' => $data['faculty_id']
        ]);

        return $program;
    }

    /**
     * Delete a program.
     *
     * @param Program $program
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteProgram(Program $program): void
    {
        // Check if program has students
        if ($program->students()->count() > 0) {
            throw new BusinessValidationException('Cannot delete program that has students enrolled.');
        }

        $program->delete();
    }

    /**
     * Get all faculties for dropdown.
     *
     * @return array
     */
    public function getFaculties(): array
    {
        return Faculty::all()->toArray();
    }
} 