<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class ProgramController extends Controller
{
    /**
     * Display the program management page
     */
    public function index()
    {
        return view('admin.program');
    }

    /**
     * Get program statistics
     */
    public function stats(): JsonResponse
    {
        $totalPrograms = Program::count();
        $programsWithStudents = Program::has('students')->count();
        $programsWithoutStudents = Program::doesntHave('students')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $totalPrograms,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'withStudents' => [
                    'total' => $programsWithStudents,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'withoutStudents' => [
                    'total' => $programsWithoutStudents,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ]
            ]
        ]);
    }

    /**
     * Get program data for DataTables
     */
    public function datatable(): JsonResponse
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
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store a newly created program
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:programs,code',
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        Program::create([
            'name' => $request->name,
            'code' => $request->code,
            'faculty_id' => $request->faculty_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program created successfully.'
        ]);
    }

    /**
     * Display the specified program
     */
    public function show(Program $program): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $program->load(['faculty', 'students'])
        ]);
    }

    /**
     * Update the specified program
     */
    public function update(Request $request, Program $program): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:programs,code,' . $program->id,
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        $program->update([
            'name' => $request->name,
            'code' => $request->code,
            'faculty_id' => $request->faculty_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program updated successfully.'
        ]);
    }

    /**
     * Remove the specified program
     */
    public function destroy(Program $program): JsonResponse
    {
        // Check if program has students
        if ($program->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete program that has students enrolled.'
            ], 422);
        }

        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program deleted successfully.'
        ]);
    }

    /**
     * Get all faculties for dropdown
     */
    public function getFaculties(): JsonResponse
    {
        $faculties = Faculty::all();
        return response()->json([
            'success' => true,
            'data' => $faculties
        ]);
    }
} 