<?php

namespace App\Services\Admin;

use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class CourseService
{
    /**
     * Get course statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalCourses = Course::count();
        $coursesWithPrerequisites = Course::has('prerequisites')->count();
        $coursesWithoutPrerequisites = Course::doesntHave('prerequisites')->count();

        $lastCreatedAtTotal = Course::max('created_at');
        $lastCreatedAtWithPrereq = Course::whereHas('prerequisites')->max('created_at');
        $lastCreatedAtWithoutPrereq = Course::whereDoesntHave('prerequisites')->max('created_at');

        return [
            'total' => [
                'total' => $totalCourses,
                'lastUpdateTime' => formatDate($lastCreatedAtTotal)
            ],
            'withPrerequisites' => [
                'total' => $coursesWithPrerequisites,
                'lastUpdateTime' => formatDate($lastCreatedAtWithPrereq)
            ],
            'withoutPrerequisites' => [
                'total' => $coursesWithoutPrerequisites,
                'lastUpdateTime' => formatDate($lastCreatedAtWithoutPrereq)
            ]
        ];
    }

    /**
     * Get course data for DataTables.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $courses = Course::with(['faculty', 'prerequisites']);

        return DataTables::of($courses)
            ->addColumn('faculty_name', function ($course) {
                return $course->faculty ? $course->faculty->name : 'N/A';
            })
            ->addColumn('prerequisites_count', function ($course) {
                return $course->prerequisites->count();
            })
            ->addColumn('prerequisites_list', function ($course) {
                return $course->prerequisites->pluck('title')->join(', ') ?: 'None';
            })
            ->addColumn('action', function ($course) {
                return $this->renderActionButtons($course);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param Course $course
     * @return string
     */
    protected function renderActionButtons($course): string
    {
        return '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item editCourseBtn" href="javascript:void(0);" data-id="' . $course->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteCourseBtn" href="javascript:void(0);" data-id="' . $course->id . '">
                        <i class="bx bx-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        ';
    }

    /**
     * Create a new course.
     *
     * @param array $data
     * @return Course
     */
    public function createCourse(array $data): Course
    {
        return Course::create([
            'code' => $data['code'],
            'title' => $data['title'],
            'credit_hours' => $data['credit_hours'],
            'faculty_id' => $data['faculty_id']
        ]);
    }

    /**
     * Get course details.
     *
     * @param Course $course
     * @return Course
     */
    public function getCourse(Course $course): Course
    {
        return $course->load(['faculty', 'prerequisites']);
    }

    /**
     * Update an existing course.
     *
     * @param Course $course
     * @param array $data
     * @return Course
     */
    public function updateCourse(Course $course, array $data): Course
    {
        $course->update([
            'code' => $data['code'],
            'title' => $data['title'],
            'credit_hours' => $data['credit_hours'],
            'faculty_id' => $data['faculty_id']
        ]);

        return $course;
    }

    /**
     * Delete a course.
     *
     * @param Course $course
     * @return void
     * @throws BusinessValidationException
     */
    public function deleteCourse(Course $course): void
    {
        $course->delete();
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
     * Get all courses for prerequisite dropdown.
     *
     * @return array
     */
    public function getCourses(): array
    {
        return Course::all()->toArray();
    }
} 