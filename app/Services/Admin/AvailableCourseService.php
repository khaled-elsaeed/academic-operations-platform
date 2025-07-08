<?php

namespace App\Services\Admin;

use App\Models\AvailableCourse;
use App\Exceptions\BusinessValidationException;
use Illuminate\Http\UploadedFile;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;

class AvailableCourseService
{
    /**
     * Create a new available course, ensuring uniqueness by course, term, program, and level.
     */
    public function createAvailableCourse(array $data): AvailableCourse
    {
        $exists = AvailableCourse::where('course_id', $data['course_id'] ?? null)
            ->where('term_id', $data['term_id'] ?? null)
            ->where('program_id', $data['program_id'] ?? null)
            ->where('level', $data['level'] ?? null)
            ->exists();

        if ($exists) {
            throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
        }

        return AvailableCourse::create($data);
    }

    /**
     * Update an existing available course.
     */
    public function updateAvailableCourse(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        $exists = AvailableCourse::where('course_id', $data['course_id'] ?? null)
            ->where('term_id', $data['term_id'] ?? null)
            ->where('program_id', $data['program_id'] ?? null)
            ->where('level', $data['level'] ?? null)
            ->where('id', '!=', $availableCourse->id)
            ->exists();

        if ($exists) {
            throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
        }

        $availableCourse->update($data);
        return $availableCourse;
    }

    /**
     * Delete an available course.
     */
    public function deleteAvailableCourse(AvailableCourse $availableCourse): void
    {
        $availableCourse->delete();
    }

    /**
     * Get datatable JSON response for available courses.
     */
    public function getDatatable(): JsonResponse
    {
        $query = AvailableCourse::with(['course', 'term', 'program']);
        return DataTables::of($query)
            ->addColumn('course', function($ac) {
                return $ac->course ? $ac->course->name : '-';
            })
            ->addColumn('term', function($ac) {
                return $ac->term ? $ac->term->name : '-';
            })
            ->addColumn('program', function($ac) {
                return $ac->program ? $ac->program->name : '-';
            })
            ->addColumn('action', function($ac) {
                return $this->renderActionButtons($ac);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     */
    protected function renderActionButtons($ac)
    {
        return '
        <div class="d-flex gap-2">
          <button type="button"
            class="btn btn-sm btn-icon btn-primary rounded-circle editAvailableCourseBtn"
            data-available-course=\'' . e(json_encode($ac)) . '\'
            title="Edit">
            <i class="bx bx-edit"></i>
          </button>
          <button type="button"
            class="btn btn-sm btn-icon btn-danger rounded-circle deleteAvailableCourseBtn"
            data-id="' . e($ac->id) . '"
            title="Delete">
            <i class="bx bx-trash"></i>
          </button>
        </div>
        ';
    }

    /**
     * Import available courses from an uploaded Excel file (stub).
     */
    public function importAvailableCourses(UploadedFile $file): array
    {
        return [
            'success' => true,
            'message' => 'All available courses imported successfully! (stub)'
        ];
    }

    /**
     * Download the available courses import template as an Excel file (stub).
     */
    public function downloadTemplate()
    {
        return response('Template download stub');
    }
} 