<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Faculty;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class ProgramService
{
    /**
     * Create a new program.
     *
     * @param array $data
     * @return Program
     * @throws BusinessValidationException
     */
    public function createProgram(array $data): Program
    {
        $existingProgram = Program::where('code', $data['code'])
            ->where('faculty_id', $data['faculty_id'])
            ->first();
        if ($existingProgram) {
            throw new BusinessValidationException('A program with this code already exists in the selected faculty.');
        }
        return Program::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'faculty_id' => $data['faculty_id']
        ]);
    }

    /**
     * Update an existing program.
     *
     * @param Program $program
     * @param array $data
     * @return Program
     * @throws BusinessValidationException
     */
    public function updateProgram(Program $program, array $data): Program
    {
        $existingProgram = Program::where('code', $data['code'])
            ->where('faculty_id', $data['faculty_id'])
            ->where('id', '!=', $program->id)
            ->first();
        if ($existingProgram) {
            throw new BusinessValidationException('A program with this code already exists in the selected faculty.');
        }
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
        if ($program->students()->count() > 0) {
            throw new BusinessValidationException('Cannot delete program that has students enrolled.');
        }
        $program->delete();
    }

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
        $lastUpdateTime = formatDate(Program::max('updated_at'));
        return [
            'total' => [
                'total' => formatNumber($totalPrograms),
                'lastUpdateTime' => $lastUpdateTime
            ],
            'withStudents' => [
                'total' => formatNumber($programsWithStudents),
                'lastUpdateTime' => $lastUpdateTime
            ],
            'withoutStudents' => [
                'total' => formatNumber($programsWithoutStudents),
                'lastUpdateTime' => $lastUpdateTime
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
        $query = Program::with(['faculty', 'students']);
        $request = request();
        $this->applySearchFilters($query, $request);
        return DataTables::of($query)
            ->addColumn('faculty_name', fn($program) => $program->faculty ? $program->faculty->name : 'N/A')
            ->addColumn('students_count', fn($program) => $program->students->count())
            ->addColumn('action', fn($program) => $this->renderActionButtons($program))
            ->rawColumns(['action'])
            ->make(true);
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

    /**
     * Get all programs (for dropdown and forms).
     *
     * @return array
     */
    public function getAll(): array
    {
        return Program::with('faculty')->get()->map(function ($program) {
            return [
                'id' => $program->id,
                'name' => $program->name,
                'code' => $program->code,
                'faculty_id' => $program->faculty_id,
                'faculty_name' => $program->faculty ? $program->faculty->name : 'N/A',
            ];
        })->toArray();
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
     * Render action buttons for datatable rows.
     *
     * @param Program $program
     * @return string
     */
    protected function renderActionButtons($program): string
    {
        return '<div class="dropdown">...'; // unchanged for brevity
    }

    /**
     * Apply search filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function applySearchFilters($query, $request): void
    {
        // Filter by program name
        $searchName = $request->input('search_name');
        if (!empty($searchName)) {
            $query->whereRaw('LOWER(programs.name) LIKE ?', ['%' . mb_strtolower($searchName) . '%']);
        }

        // Filter by program code
        $searchCode = $request->input('search_code');
        if (!empty($searchCode)) {
            $query->whereRaw('LOWER(programs.code) LIKE ?', ['%' . mb_strtolower($searchCode) . '%']);
        }

        // Filter by faculty
        $searchFaculty = $request->input('search_faculty');
        if (!empty($searchFaculty)) {
            $query->where('programs.faculty_id', $searchFaculty);
        }
    }
} 