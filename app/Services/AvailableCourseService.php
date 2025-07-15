<?php

namespace App\Services;

use App\Models\AvailableCourse;
use App\Models\Course;
use App\Models\CourseEligibility;
use App\Models\Level;
use App\Models\Program;
use App\Models\Term;
use App\Exceptions\BusinessValidationException;
use App\Imports\AvailableCoursesImport;
use App\Validators\AvailableCourseImportValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class AvailableCourseService
{
    /**
     * Create a new available course or multiple courses in bulk.
     *
     * @param array $data
     * @return AvailableCourse|array
     * @throws BusinessValidationException
     */
    public function createAvailableCourse(array $data)
    {
        // Bulk creation support
        if (isset($data['courses']) && is_array($data['courses'])) {
            $results = [];
            foreach ($data['courses'] as $courseData) {
                $results[] = $this->createAvailableCourseSingle($courseData);
            }
            return $results;
        }
        return $this->createAvailableCourseSingle($data);
    }

    /**
     * Create a single available course with eligibility mode support.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function createAvailableCourseSingle(array $data): AvailableCourse
    {
        $this->validateAvailableCourseData($data);
        $this->ensureAvailableCourseDoesNotExist($data);

        return DB::transaction(function () use ($data) {
            $isUniversal = $data['is_universal'] ?? false;
            $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
            $availableCourse = $this->createAvailableCourseRecord($data);
            if (!$isUniversal && $eligibilityMode !== 'universal') {
                $eligibility = $data['eligibility'] ?? [];
                if ($eligibilityMode === 'all_programs') {
                    // All programs for a specific level
                    $levelId = $eligibility[0]['level_id'] ?? null;
                    $allPrograms = Program::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allPrograms as $pid) {
                        $bulkEligibility[] = ['program_id' => $pid, 'level_id' => $levelId];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } elseif ($eligibilityMode === 'all_levels') {
                    // All levels for a specific program
                    $programId = $eligibility[0]['program_id'] ?? null;
                    $allLevels = Level::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allLevels as $lid) {
                        $bulkEligibility[] = ['program_id' => $programId, 'level_id' => $lid];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } else {
                    // Individual mode (custom pairs)
                    $this->attachEligibilities($availableCourse, $eligibility);
                }
            }
            return $availableCourse->fresh(['programs', 'levels']);
        });
    }

    /**
     * Update an existing available course or multiple courses in bulk.
     *
     * @param int|AvailableCourse $availableCourseOrId
     * @param array $data
     * @return AvailableCourse|array
     * @throws BusinessValidationException
     */
    public function updateAvailableCourse($availableCourseOrId, array $data)
    {
        // Bulk update support
        if (isset($data['courses']) && is_array($data['courses'])) {
            $results = [];
            foreach ($data['courses'] as $courseData) {
                $id = $courseData['id'] ?? null;
                if (!$id) continue;
                $results[] = $this->updateAvailableCourseById($id, $courseData);
            }
            return $results;
        }
        if ($availableCourseOrId instanceof AvailableCourse) {
            return $this->updateAvailableCourseSingle($availableCourseOrId, $data);
        }
        $availableCourse = AvailableCourse::findOrFail($availableCourseOrId);
        return $this->updateAvailableCourseSingle($availableCourse, $data);
    }

    /**
     * Update a single available course with eligibility mode support.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function updateAvailableCourseSingle(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        $this->validateAvailableCourseData($data);
        $this->ensureAvailableCourseDoesNotExist($data, $availableCourse->id);

        return DB::transaction(function () use ($availableCourse, $data) {
            $isUniversal = $data['is_universal'] ?? false;
            $eligibilityMode = $data['eligibility_mode'] ?? 'individual';
            $availableCourse->update([
                'course_id' => $data['course_id'],
                'term_id' => $data['term_id'],
                'min_capacity' => $data['min_capacity'] ?? 1,
                'max_capacity' => $data['max_capacity'] ?? 30,
                'is_universal' => $isUniversal,
            ]);
            if (!$isUniversal && $eligibilityMode !== 'universal') {
                $eligibility = $data['eligibility'] ?? [];
                if ($eligibilityMode === 'all_programs') {
                    $levelId = $eligibility[0]['level_id'] ?? null;
                    $allPrograms = Program::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allPrograms as $pid) {
                        $bulkEligibility[] = ['program_id' => $pid, 'level_id' => $levelId];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } elseif ($eligibilityMode === 'all_levels') {
                    $programId = $eligibility[0]['program_id'] ?? null;
                    $allLevels = Level::pluck('id')->toArray();
                    $bulkEligibility = [];
                    foreach ($allLevels as $lid) {
                        $bulkEligibility[] = ['program_id' => $programId, 'level_id' => $lid];
                    }
                    $this->attachEligibilities($availableCourse, $bulkEligibility);
                } else {
                    $this->attachEligibilities($availableCourse, $eligibility);
                }
            } else {
                $availableCourse->setProgramLevelPairs([]);
            }
            return $availableCourse->fresh(['programs', 'levels']);
        });
    }

    /**
     * Delete an available course by ID.
     *
     * @param int $id
     * @return void
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
     * Get available course statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $latest = AvailableCourse::max('updated_at');
        $total = AvailableCourse::count();
        $universal = AvailableCourse::where('is_universal', true)->count();
        return [
            'available_courses' => [
                'total' => $total,
                'lastUpdateTime' => formatDate($latest),
            ],
            'universal_courses' => [
                'total' => $universal,
                'lastUpdateTime' => formatDate($latest),
            ],
        ];
    }

    /**
     * Get DataTables JSON response for available courses.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $query = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level']);
        $request = request();
        $this->applySearchFilters($query, $request);

        return DataTables::of($query)
            ->addColumn('course', function ($availableCourse) {
                return $availableCourse->course?->name ?? '-';
            })
            ->addColumn('term', function ($availableCourse) {
                return $availableCourse->term?->name ?? '-';
            })
            ->addColumn('eligibility', function ($availableCourse) {
                if ($availableCourse->is_universal) {
                    return '<span class="badge bg-primary">Universal</span>';
                }
                $pairs = $availableCourse->eligibilities->map(function ($eligibility) {
                    $programName = $eligibility->program?->name ?? '-';
                    $levelName = $eligibility->level?->name ?? '-';
                    return "{$programName} / {$levelName}";
                });
                $count = $pairs->count();
                if ($count === 0) {
                    return '-';
                }
                if ($count === 1) {
                    return e($pairs->first());
                }
                return sprintf(
                    '<button type="button" class="btn btn-outline-info btn-sm show-eligibility-modal position-relative group-hover-parent" data-eligibility-pairs="%s" data-ac-id="%d" title="View Eligibility Details" style="position: relative;">
                        <i class="bx bx-list-ul"></i> Eligibility 
                        <span class="badge bg-info eligibility-badge-hover" style="transition: background-color 0.2s, color 0.2s;">%d</span>
                    </button>',
                    e(json_encode($pairs->toArray())),
                    $availableCourse->id,
                    $count
                );
            })
            ->addColumn('capacity', function ($availableCourse) {
                return "{$availableCourse->min_capacity} - {$availableCourse->max_capacity}";
            })
            ->addColumn('action', function ($availableCourse) {
                return $this->renderActionButtons($availableCourse);
            })
            ->rawColumns(['eligibility', 'action'])
            ->orderColumn('course', function ($query, $order) {
                $query->join('courses', 'available_courses.course_id', '=', 'courses.id')
                    ->orderBy('courses.title', $order)
                    ->select('available_courses.*');
            })
            ->orderColumn('term', function ($query, $order) {
                $query->join('terms', 'available_courses.term_id', '=', 'terms.id')
                    ->orderBy('terms.year', $order)
                    ->orderBy('terms.season', $order)
                    ->select('available_courses.*');
            })
            ->make(true);
    }

    /**
     * Apply search filters to the available courses query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function applySearchFilters($query, $request): void
    {
        // --- Course Title/Code Filter ---
        $searchCourse = $request->input('search_course');
        if (!empty($searchCourse)) {
            $query->whereHas('course', function ($q) use ($searchCourse) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . mb_strtolower($searchCourse) . '%'])
                  ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchCourse) . '%']);
            });
        }

        // --- Term Season/Year/Code Filter ---
        $searchTerm = $request->input('search_term');
        if (!empty($searchTerm)) {
            $query->whereHas('term', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(season) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%'])
                  ->orWhereRaw('CAST(year AS CHAR) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%'])
                  ->orWhereRaw('LOWER(code) LIKE ?', ['%' . mb_strtolower($searchTerm) . '%']);
            });
        }

        // --- Universal Status Filter ---
        $isUniversal = $request->input('search_is_universal');
        if ($isUniversal !== null && $isUniversal !== '') {
            $boolVal = filter_var($isUniversal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($boolVal !== null) {
                $query->where('is_universal', $boolVal);
            }
        }
    }

    /**
     * Import available courses from uploaded Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importAvailableCoursesFromFile(UploadedFile $file): array
    {
        try {
            $import = new AvailableCoursesImport();
            Excel::import($import, $file);
            $rows = $import->rows ?? collect();
            return $this->importAvailableCoursesFromRows($rows);
        } catch (\Exception $e) {
            Log::error('Failed to import available courses', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to process the uploaded file.',
                'errors' => [$e->getMessage()],
                'created' => 0,
            ];
        }
    }

    /**
     * Import available courses from collection of rows.
     *
     * @param Collection $rows
     * @return array
     */
    public function importAvailableCoursesFromRows(Collection $rows): array
    {
        $errors = [];
        $created = 0;
        $skipped = 0;
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            try {
                DB::transaction(function () use ($row, $rowNum, &$created) {
                    $result = $this->processImportRow($row->toArray(), $rowNum);
                    if ($result === 'created') {
                        $created++;
                    } else {
                        $skipped++;
                    }
                });
            } catch (ValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => $e->errors()["Row {$rowNum}"] ?? [],
                    'original_data' => $row->toArray(),
                ];
            } catch (BusinessValidationException $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => [$e->getMessage()]],
                    'original_data' => $row->toArray(),
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'errors' => ['general' => ['Unexpected error - ' . $e->getMessage()]],
                    'original_data' => $row->toArray(),
                ];
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
        $totalProcessed = $created + $skipped;
        $message = empty($errors) 
            ? "Successfully processed {$totalProcessed} available courses. ({$created} created, {$skipped} skipped)." 
            : "Import completed with {$totalProcessed} successful ({$created} created, {$skipped} skipped) and " . count($errors) . " failed rows.";
        $success = empty($errors);
        return [
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'imported_count' => $totalProcessed,
            'created_count' => $created,
            'skipped_count' => $skipped,
        ];
    }

    /**
     * Process a single import row.
     *
     * @param array $row
     * @param int $rowNum
     * @return string 'created' or 'skipped'
     * @throws ValidationException|BusinessValidationException
     */
    private function processImportRow(array $row, int $rowNum): string
    {
        AvailableCourseImportValidator::validateRow($row, $rowNum);
        $course = $this->findCourseByCode($row['course_code'] ?? '');
        $term = $this->findTermByCode($row['term_code'] ?? '');
        $programName = $row['program_name'] ?? null;
        $levelName = $row['level_name'] ?? null;
        if ((empty($programName) && empty($levelName))) {
            $this->checkForDuplicateImportCourse($course, $term, null, null, true);
            AvailableCourse::create([
                'course_id' => $course->id,
                'term_id' => $term->id,
                'min_capacity' => $row['min_capacity'] ?? 1,
                'max_capacity' => $row['max_capacity'] ?? 30,
                'is_universal' => true,
            ]);
            return 'created';
        } else {
            $program = $this->findProgramByName($programName);
            $level = $this->findLevelByName($levelName);
            $this->checkForDuplicateImportCourse($course, $term, $program, $level, false);
            $availableCourse = AvailableCourse::create([
                'course_id' => $course->id,
                'term_id' => $term->id,
                'min_capacity' => $row['min_capacity'] ?? 1,
                'max_capacity' => $row['max_capacity'] ?? 30,
                'is_universal' => false,
            ]);
            CourseEligibility::create([
                'available_course_id' => $availableCourse->id,
                'program_id' => $program->id,
                'level_id' => $level->id,
            ]);
            return 'created';
        }
    }

    // --- Private helpers for finding related models and validation ---

    private function findCourseByCode(string $code): Course
    {
        $course = Course::where('code', $code)->first();
        if (!$course) {
            throw new BusinessValidationException("Course with code '{$code}' not found.");
        }
        return $course;
    }

    private function findTermByCode(string $code): Term
    {
        $term = Term::where('code', $code)->first();
        if (!$term) {
            throw new BusinessValidationException("Term with code '{$code}' not found.");
        }
        return $term;
    }

    private function findProgramByName(string $name): Program
    {
        $program = Program::where('name', $name)->first();
        if (!$program) {
            throw new BusinessValidationException("Program '{$name}' not found.");
        }
        return $program;
    }

    private function findLevelByName(string $name): Level
    {
        $level = Level::where('name', $name)->first();
        if (!$level) {
            throw new BusinessValidationException("Level '{$name}' not found.");
        }
        return $level;
    }

    private function validateAvailableCourseData(array $data): void
    {
        $minCapacity = $data['min_capacity'] ?? 1;
        $maxCapacity = $data['max_capacity'] ?? 30;
        if ($minCapacity > $maxCapacity) {
            throw new BusinessValidationException('Minimum capacity cannot be greater than maximum capacity.');
        }
        if ($minCapacity < 0 || $maxCapacity < 0) {
            throw new BusinessValidationException('Capacity values cannot be negative.');
        }
    }

    private function ensureAvailableCourseDoesNotExist(array $data, int $excludeId = null): void
    {
        $isUniversal = $data['is_universal'] ?? false;
        if ($isUniversal) {
            if ($this->universalAvailableCourseExists($data, $excludeId)) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
            if ($this->availableCourseEligibilitiesExist($data, $excludeId)) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

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

    private function attachEligibilities(AvailableCourse $availableCourse, array $eligibility): void
    {
        $pairs = collect($eligibility)
            ->filter(function ($pair) {
                return isset($pair['program_id']) && isset($pair['level_id']);
            })
            ->map(function ($pair) {
                return [
                    'program_id' => $pair['program_id'],
                    'level_id' => $pair['level_id'],
                ];
            })
            ->toArray();
        $availableCourse->setProgramLevelPairs($pairs);
    }

    private function checkForDuplicateImportCourse(Course $course, Term $term, ?Program $program = null, ?Level $level = null, bool $isUniversal = false): void
    {
        if ($isUniversal) {
            $exists = AvailableCourse::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('is_universal', true)
                ->exists();
            if ($exists) {
                throw new BusinessValidationException('A universal available course for this Course and Term already exists.');
            }
        } else {
            if (!$program || !$level) {
                throw new BusinessValidationException('Program and Level are required for non-universal available courses.');
            }
            $exists = AvailableCourse::where('course_id', $course->id)
                ->where('term_id', $term->id)
                ->where('is_universal', false)
                ->whereHas('eligibilities', function ($q) use ($program, $level) {
                    $q->where('program_id', $program->id)
                      ->where('level_id', $level->id);
                })
                ->exists();
            if ($exists) {
                throw new BusinessValidationException('An available course with the same Course, Term, Program, and Level already exists.');
            }
        }
    }

    // --- Additional methods for fetching and formatting data ---

    public function getAvailableCourseWithEligibilities(int $id): AvailableCourse
    {
        return AvailableCourse::with(['eligibilities.program', 'eligibilities.level', 'course', 'term'])
            ->findOrFail($id);
    }

    public function getAvailableCourse(int $id): array
    {
        $availableCourse = $this->getAvailableCourseWithEligibilities($id);
        return [
            'id' => $availableCourse->id,
            'course_id' => $availableCourse->course_id,
            'term_id' => $availableCourse->term_id,
            'min_capacity' => $availableCourse->min_capacity,
            'max_capacity' => $availableCourse->max_capacity,
            'is_universal' => (bool) $availableCourse->is_universal,
            'eligibilities' => $availableCourse->eligibilities->map(function($eligibility) {
                return [
                    'program_id' => $eligibility->program_id,
                    'level_id' => $eligibility->level_id,
                    'program_name' => $eligibility->program?->name,
                    'level_name' => $eligibility->level?->name,
                ];
            })->toArray(),
        ];
    }

    public function updateAvailableCourseById(int $id, array $data): AvailableCourse
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return $this->updateAvailableCourse($availableCourse, $data);
    }

    public function getAll(): Collection
    {
        return AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level'])
            ->get()
            ->map(function ($availableCourse) {
                return [
                    'id' => $availableCourse->id,
                    'course_id' => $availableCourse->course_id,
                    'course_name' => $availableCourse->course?->name ?? '-',
                    'course_code' => $availableCourse->course?->code ?? '-',
                    'term_id' => $availableCourse->term_id,
                    'term_name' => $availableCourse->term?->name ?? '-',
                    'term_code' => $availableCourse->term?->code ?? '-',
                    'min_capacity' => $availableCourse->min_capacity,
                    'max_capacity' => $availableCourse->max_capacity,
                    'is_universal' => (bool) $availableCourse->is_universal,
                    'eligibilities' => $availableCourse->eligibilities->map(function($eligibility) {
                        return [
                            'program_id' => $eligibility->program_id,
                            'level_id' => $eligibility->level_id,
                            'program_name' => $eligibility->program?->name,
                            'level_name' => $eligibility->level?->name,
                        ];
                    })->toArray(),
                ];
            });
    }

    /**
     * Render action buttons for DataTables.
     *
     * @param AvailableCourse $availableCourse
     * @return string
     */
    private function renderActionButtons(AvailableCourse $availableCourse): string
    {
        $editUrl = route('available_courses.edit', $availableCourse->id);
        return sprintf(
            '<div class="d-flex gap-2">
                <a href="%s" class="btn btn-sm btn-icon btn-primary rounded-circle" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
                <button type="button" class="btn btn-sm btn-icon btn-danger rounded-circle deleteAvailableCourseBtn" 
                        data-id="%d" title="Delete">
                    <i class="bx bx-trash"></i>
                </button>
            </div>',
            e($editUrl),
            $availableCourse->id
        );
    }
}