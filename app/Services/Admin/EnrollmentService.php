<?php

namespace App\Services\Admin;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Models\Term;
use Yajra\DataTables\DataTables;

class EnrollmentService
{
    public function createEnrollment(array $data): Enrollment
    {
        return Enrollment::create($data);
    }

    public function updateEnrollment(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update($data);
        return $enrollment;
    }

    public function deleteEnrollment(Enrollment $enrollment): void
    {
        $enrollment->delete();
    }

    public function getStats(): array
    {
        $latest = Enrollment::latest('created_at')->value('created_at');
        return [
            'enrollments' => [
                'total' => Enrollment::count(),
                'lastUpdateTime' => formatDate($latest),
            ],
        ];
    }

    public function getDatatable(): \Illuminate\Http\JsonResponse
    {
        $query = Enrollment::with(['student', 'course', 'term']);
        if (request()->has('student_id')) {
            $query->where('student_id', request('student_id'));
        }
        return DataTables::of($query)
            ->addColumn('student', function($enrollment) {
                return $enrollment->student ? $enrollment->student->name_en : '-';
            })
            ->addColumn('course', function($enrollment) {
                return $enrollment->course ? $enrollment->course->name : '-';
            })
            ->addColumn('term', function($enrollment) {
                return $enrollment->term ? $enrollment->term->name : '-';
            })
            ->addColumn('action', function($enrollment) {
                return $this->renderActionButtons($enrollment);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    protected function renderActionButtons($enrollment)
    {
        return '
        <div class="d-flex gap-2">
          <button type="button"
            class="btn btn-sm btn-icon btn-primary rounded-circle editEnrollmentBtn"
            data-enrollment=\'' . e(json_encode($enrollment)) . '\'
            title="Edit">
            <i class="bx bx-edit"></i>
          </button>
          <button type="button"
            class="btn btn-sm btn-icon btn-danger rounded-circle deleteEnrollmentBtn"
            data-id="' . e($enrollment->id) . '"
            title="Delete">
            <i class="bx bx-trash"></i>
          </button>
        </div>
        ';
    }

    public function getStudentEnrollments($studentId)
    {
        return Enrollment::with(['course', 'term'])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();
    }
} 