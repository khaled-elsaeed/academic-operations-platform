<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\CoursePrerequisite;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessValidationException;

class CourseService
{
    private const FACULTY_NAME = 'Faculty of Computer Science & Engineering';

    /**
     * Create a new course.
     *
     * @param array $data
     * @return Course
     * @throws BusinessValidationException
     */
    public function createCourse(array $data): Course
    {
        $existingCourse = Course::where('code', $data['code'])
            ->where('faculty_id', $data['faculty_id'])
            ->first();
        if ($existingCourse) {
            throw new BusinessValidationException('A course with this code already exists in the selected faculty.');
        }
        return Course::create([
            'code' => $data['code'],
            'title' => $data['title'],
            'credit_hours' => $data['credit_hours'],
            'faculty_id' => $data['faculty_id']
        ]);
    }

    /**
     * Update an existing course.
     *
     * @param Course $course
     * @param array $data
     * @return Course
     * @throws BusinessValidationException
     */
    public function updateCourse(Course $course, array $data): Course
    {
        $existingCourse = Course::where('code', $data['code'])
            ->where('faculty_id', $data['faculty_id'])
            ->where('id', '!=', $course->id)
            ->first();
        if ($existingCourse) {
            throw new BusinessValidationException('A course with this code already exists in the selected faculty.');
        }
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
     * Get course statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $totalCourses = Course::whereHas('faculty', function($query) {
            $query->where('name', self::FACULTY_NAME);
        })->count();
        $coursesWithPrerequisites = Course::whereHas('faculty', function($query) {
                $query->where('name', self::FACULTY_NAME);
            })
            ->has('prerequisites')
            ->count();
        $coursesWithoutPrerequisites = Course::whereHas('faculty', function($query) {
                $query->where('name', self::FACULTY_NAME);
            })
            ->doesntHave('prerequisites')
            ->count();
        $lastCreatedAtTotal = Course::whereHas('faculty', function($query) {
            $query->where('name', self::FACULTY_NAME);
        })->max('created_at');
        $lastCreatedAtWithPrereq = Course::whereHas('faculty', function($query) {
                $query->where('name', self::FACULTY_NAME);
            })
            ->whereHas('prerequisites')
            ->max('created_at');
        $lastCreatedAtWithoutPrereq = Course::whereHas('faculty', function($query) {
                $query->where('name', self::FACULTY_NAME);
            })
            ->whereDoesntHave('prerequisites')
            ->max('created_at');
        return [
            'total' => [
                'total' => formatNumber($totalCourses),
                'lastUpdateTime' => formatDate($lastCreatedAtTotal)
            ],
            'withPrerequisites' => [
                'total' => formatNumber($coursesWithPrerequisites),
                'lastUpdateTime' => formatDate($lastCreatedAtWithPrereq)
            ],
            'withoutPrerequisites' => [
                'total' => formatNumber($coursesWithoutPrerequisites),
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
        $query = Course::with(['faculty', 'prerequisites'])
            ->leftJoin('faculties', 'courses.faculty_id', '=', 'faculties.id')
            ->leftJoin('course_prerequisite', 'courses.id', '=', 'course_prerequisite.course_id')
            ->select('courses.*', DB::raw('COUNT(course_prerequisite.prerequisite_id) as prerequisites_count'))
            ->groupBy('courses.id', 'courses.code', 'courses.title', 'courses.credit_hours', 'courses.faculty_id', 'courses.created_at', 'courses.updated_at');
        $request = request();
        $this->applySearchFilters($query, $request);
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('faculty_name', fn($course) => $course->faculty ? $course->faculty->name : 'N/A')
            ->addColumn('prerequisites_count', fn($course) => $course->prerequisites_count ?? 0)
            ->addColumn('prerequisites_list', fn($course) => $course->prerequisites->pluck('title')->join(', ') ?: 'None')
            ->addColumn('action', fn($course) => $this->renderActionButtons($course))
            ->orderColumn('faculty_name', 'faculties.name $1')
            ->orderColumn('prerequisites_count', 'prerequisites_count $1')
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Get all courses (for dropdown and forms).
     *
     * @return array
     */
    public function getAll(): array
    {
        return Course::with('faculty')->get()->map(function ($course) {
            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->title,
                'title' => $course->title,
                'credit_hours' => $course->credit_hours,
                'faculty_id' => $course->faculty_id,
                'faculty_name' => $course->faculty ? $course->faculty->name : 'N/A',
            ];
        })->toArray();
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
     * Get prerequisites for selected courses and check enrollment status.
     *
     * @param int $studentId
     * @param array $courseIds
     * @return array
     */
    public function getPrerequisites(int $studentId, array $courseIds): array
    {
        return CoursePrerequisite::with(['prerequisiteCourse', 'course'])
            ->whereIn('course_id', function($query) use ($courseIds) {
                $query->select('course_id')
                    ->from('available_courses')
                    ->whereIn('id', $courseIds);
            })
            ->get()
            ->map(function($prereq) use ($studentId, $courseIds) {
                $isEnrolled = Enrollment::where('student_id', $studentId)
                    ->where('course_id', $prereq->prerequisite_id)
                    ->exists();
                
                // Find the corresponding available_course_id
                $availableCourseId = \DB::table('available_courses')
                    ->where('course_id', $prereq->course_id)
                    ->whereIn('id', $courseIds)
                    ->value('id');
                
                return [
                    'course_name' => $prereq->prerequisiteCourse->name ?? 'Unknown Course',
                    'course_code' => $prereq->prerequisiteCourse->code ?? 'N/A',
                    'credit_hours' => $prereq->prerequisiteCourse->credit_hours ?? 0,
                    'required_for' => $prereq->course->name ?? 'Unknown Course',
                    'required_for_course_id' => $availableCourseId,
                    'prerequisite_course_id' => $prereq->prerequisite_id,
                    'is_enrolled' => $isEnrolled,
                ];
            })->toArray();
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
                <button type="button" class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item editCourseBtn" href="javascript:void(0);" data-id="' . $course->id . '">
                        <i class="bx bx-edit-alt me-1"></i> Edit
                    </a>
                    <a class="dropdown-item deleteCourseBtn" href="javascript:void(0);" data-id="' . $course->id . '">
                        <i class="bx bx-trash text-danger me-1"></i> Delete                    </a>
                </div>
            </div>
        ';
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
        // Filter by course code
        $searchCode = $request->input('search_code');
        if (!empty($searchCode)) {
            $query->whereRaw('LOWER(courses.code) LIKE ?', ['%' . mb_strtolower($searchCode) . '%']);
        }

        // Filter by course title
        $searchTitle = $request->input('search_title');
        if (!empty($searchTitle)) {
            $query->whereRaw('LOWER(courses.title) LIKE ?', ['%' . mb_strtolower($searchTitle) . '%']);
        }

        // Filter by faculty
        $searchFaculty = $request->input('search_faculty');
        if (!empty($searchFaculty)) {
            $query->where('courses.faculty_id', $searchFaculty);
        }
    }
} 