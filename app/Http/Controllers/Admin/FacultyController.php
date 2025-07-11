<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class FacultyController extends Controller
{
    /**
     * Display the faculty management page
     */
    public function index()
    {
        return view('admin.faculty');
    }

    /**
     * Get faculty statistics
     */
    public function stats(): JsonResponse
    {
        $totalFaculties = Faculty::count();
        $facultiesWithPrograms = Faculty::has('programs')->count();
        $facultiesWithoutPrograms = Faculty::doesntHave('programs')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total' => $totalFaculties,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'withPrograms' => [
                    'total' => $facultiesWithPrograms,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ],
                'withoutPrograms' => [
                    'total' => $facultiesWithoutPrograms,
                    'lastUpdateTime' => now()->format('Y-m-d H:i:s')
                ]
            ]
        ]);
    }

    /**
     * Get faculty data for DataTables
     */
    public function datatable(): JsonResponse
    {
        $faculties = Faculty::with('programs');

        return DataTables::of($faculties)
            ->addColumn('programs_count', function ($faculty) {
                return $faculty->programs->count();
            })
            ->addColumn('action', function ($faculty) {
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
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store a newly created faculty
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name'
        ]);

        Faculty::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faculty created successfully.'
        ]);
    }

    /**
     * Display the specified faculty
     */
    public function show(Faculty $faculty): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $faculty->load('programs')
        ]);
    }

    /**
     * Update the specified faculty
     */
    public function update(Request $request, Faculty $faculty): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name,' . $faculty->id
        ]);

        $faculty->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faculty updated successfully.'
        ]);
    }

    /**
     * Remove the specified faculty
     */
    public function destroy(Faculty $faculty): JsonResponse
    {
        // Check if faculty has programs
        if ($faculty->programs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete faculty that has programs assigned.'
            ], 422);
        }

        $faculty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Faculty deleted successfully.'
        ]);
    }
} 