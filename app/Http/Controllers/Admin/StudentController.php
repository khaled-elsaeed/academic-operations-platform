<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use App\Services\Admin\StudentService;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use Illuminate\Support\Facades\Storage;
use App\Exports\StudentsTemplateExport;

class StudentController extends Controller
{

    public function __construct(protected StudentService $studentService)
    {}

    public function index()
    {
        return view('admin.student');
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $student = $this->studentService->createStudent($validated);
        return response()->json(['success' => true, 'student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $validated = $request->validated();
        $student = $this->studentService->updateStudent($student, $validated);
        return response()->json(['success' => true, 'student' => $student]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $this->studentService->deleteStudent($student);
        return response()->json(['success' => true]);
    }

    public function datatable()
    {
        return $this->studentService->getDatatable();
    }


    public function stats()
    {
        $stats = $this->studentService->getStats();
        return response()->json($stats);
    }

    public function import(Request $request)
    {
        $request->validate([
            'students_file' => 'required|file|mimes:xlsx,xls'
        ]);

        $result = $this->studentService->importStudents($request->file('students_file'));
        return response()->json($result);
    }

    public function downloadTemplate()
    {
        return $this->studentService->downloadTemplate();
    }
} 