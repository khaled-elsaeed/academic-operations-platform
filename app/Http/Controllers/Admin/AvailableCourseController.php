<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\AvailableCourseService;

class AvailableCourseController extends Controller
{
    protected $service;

    public function __construct(AvailableCourseService $service)
    {
        $this->service = $service;
    }

    // Show the available courses page
    public function index()
    {
        return view('admin.available_course');
    }

    // DataTable AJAX
    public function datatable()
    {
        return $this->service->getDatatable();
    }

    // Store a new available course
    public function store(Request $request)
    {
        try {
            $availableCourse = $this->service->createAvailableCourse($request->all());
            return response()->json(['message' => 'Available course created successfully.', 'data' => $availableCourse]);
        } catch (\App\Exceptions\BusinessValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }

    // Update an available course
    public function update(Request $request, $id)
    {
        $availableCourse = $this->service->updateAvailableCourse(\App\Models\AvailableCourse::findOrFail($id), $request->all());
        return response()->json(['message' => 'Available course updated successfully.', 'data' => $availableCourse]);
    }

    // Delete an available course
    public function destroy($id)
    {
        $this->service->deleteAvailableCourse(\App\Models\AvailableCourse::findOrFail($id));
        return response()->json(['message' => 'Available course deleted successfully.']);
    }

    // Import available courses from Excel
    public function import(Request $request)
    {
        $request->validate([
            'available_courses_file' => 'required|file|mimes:xlsx,xls',
        ]);
        $result = $this->service->importAvailableCourses($request->file('available_courses_file'));
        return response()->json($result);
    }

    // Download available courses import template
    public function downloadTemplate()
    {
        return $this->service->downloadTemplate();
    }
} 