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
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    public function createAvailableCourse(array $data): AvailableCourse
    {
        $this->ensureAvailableCourseDoesNotExist($data);
        $isUniversal = $data['is_universal'] ?? false;
        $availableCourse = $this->createAvailableCourseRecord($data);
        if (!$isUniversal) {
            $eligibility = $data['eligibility'] ?? [];
            $this->attachEligibilities($availableCourse, $eligibility);
        }
        return $availableCourse->fresh(['programs', 'levels']);
    }

    /**
     * Ensure that an available course with the same uniqueness constraints does not already exist.
     *
     * @param array $data
     * @param int|null $excludeId
     * @throws BusinessValidationException
     */
    private function ensureAvailableCourseDoesNotExist(array $data, int $excludeId = null): void
    {
        $isUniversal = $data['is_universal'] ?? false;
        if ($isUniversal) {
            $exists = $this->universalAvailableCourseExists($data, $excludeId);
            if ($exists) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
            $exists = $this->availableCourseEligibilitiesExist($data, $excludeId);
            if ($exists) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

    /**
     * Check if a universal available course exists for the given course and term, excluding a specific ID if provided.
     *
     * @param array $data
     * @param int|null $excludeId
     */
    private function universalAvailableCourseExists(array $data, int $excludeId = null): bool
    {
        $query = AvailableCourse::where('course_id', $data['course_id'])
            ->where('term_id', $data['term_id'])
            ->where('is_universal', true);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Check if an available course with the same course, term, program, and level exists, excluding a specific ID if provided.
     *
     * @param array $data
     * @param int|null $excludeId
     */
    private function availableCourseEligibilitiesExist(array $data, int $excludeId = null): bool
    {
        $programIds = $data['program_ids'] ?? [];
        $levels = $data['levels'] ?? [];

        foreach ($programIds as $programId) {
            foreach ($levels as $level) {
                $query = AvailableCourse::where('course_id', $data['course_id'])
                    ->where('term_id', $data['term_id'])
                    ->whereHas('eligibilities', function ($q) use ($programId, $level) {
                        $q->where('program_id', $programId)->where('level_id', $level);
                    });
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                if ($query->exists()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create the AvailableCourse record.
     *
     * @param array $data
     */
    private function createAvailableCourseRecord(array $data): AvailableCourse
    {
        return AvailableCourse::create([
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id'],
            'min_capacity' => $data['min_capacity'] ?? 1,
            'max_capacity' => $data['max_capacity'] ?? 30,
            'is_universal' => $data['is_universal'] ?? false,
        ]);
    }

    /**
     * Attach eligibilities (program-level pairs) to the available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $eligibility
     */
    private function attachEligibilities(AvailableCourse $availableCourse, array $eligibility): void
    {
        $pairs = [];
        foreach ($eligibility as $pair) {
            if (isset($pair['program_id']) && isset($pair['level_id'])) {
                $pairs[] = [
                    'program_id' => $pair['program_id'],
                    'level_id' => $pair['level_id'],
                ];
            }
        }
        $availableCourse->setProgramLevelPairs($pairs);
    }

    /**
     * Update an existing available course, ensuring uniqueness by course, term, program, and level.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @throws BusinessValidationException
     */
    public function updateAvailableCourse(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        $this->ensureAvailableCourseDoesNotExist($data, $availableCourse->id);
        $isUniversal = $data['is_universal'] ?? false;
        $availableCourse->update([
            'course_id' => $data['course_id'],
            'term_id' => $data['term_id'],
            'min_capacity' => $data['min_capacity'] ?? 1,
            'max_capacity' => $data['max_capacity'] ?? 30,
            'is_universal' => $isUniversal,
        ]);
        if (!$isUniversal) {
            $eligibility = $data['eligibility'] ?? [];
            $this->attachEligibilities($availableCourse, $eligibility);
        } else {
            $availableCourse->setProgramLevelPairs([]);
        }
        return $availableCourse->fresh(['programs', 'levels']);
    }

    /**
     * Delete an available course.
     *
     * @param int $id
     * @throws BusinessValidationException
     */
    public function deleteAvailableCourse(int $id): void
    {
        $availableCourse = AvailableCourse::find($id);

        if (!$availableCourse) {
            throw new BusinessValidationException('Available course not found.');
        }

        $availableCourse->delete();
    }

    /**
     * Get datatable JSON response for available courses.
     */
    public function getDatatable(): JsonResponse
    {
        $query = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level']);
        return DataTables::of($query)
            ->addColumn('course', function ($ac) {
                return $ac->course ? $ac->course->name : '-';
            })
            ->addColumn('term', function ($ac) {
                return $ac->term ? $ac->term->name : '-';
            })
            ->addColumn('eligibility', function ($ac) {
                $pairs = $ac->eligibilities->map(function ($e) {
                    return ($e->program ? $e->program->name : '-') . ' / ' . ($e->level ? $e->level->name : '-');
                });
                $count = $pairs->count();
                if ($count === 0) return '-';
                if ($count === 1) return e($pairs->first());
                return '<button type="button" class="btn btn-outline-info show-eligibility-modal" data-eligibility-pairs="' . e(json_encode($pairs)) . '" data-ac-id="' . e($ac->id) . '">Eligibility <span class="badge">' . $count . '</span></button>';
            })
            ->addColumn('action', function ($ac) {
                return $this->renderActionButtons($ac);
            })
            ->rawColumns(['program', 'level', 'eligibility', 'action'])
            ->make(true);
    }

    /**
     * Render action buttons for datatable rows.
     *
     * @param AvailableCourse $ac
     */
    protected function renderActionButtons($ac): string
    {
        $editUrl = route('admin.available_courses.edit', $ac->id);
        return '
        <div class="d-flex gap-2">
          <a href="' . e($editUrl) . '"
            class="btn btn-sm btn-icon btn-primary rounded-circle"
            title="Edit">
            <i class="bx bx-edit"></i>
          </a>
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
     * Update available course by id.
     *
     * @param int $id
     * @param array $data
     */
    public function updateAvailableCourseById($id, array $data): AvailableCourse
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return $this->updateAvailableCourse($availableCourse, $data);
    }

    /**
     * Get available course with eligibilities by id.
     *
     * @param int $id
     */
    public function getAvailableCourseWithEligibilities($id)
    {
        return AvailableCourse::with('eligibilities')->findOrFail($id);
    }
} 