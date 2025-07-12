<?php

namespace App\Services\Admin;

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
     * Create a new available course with transaction safety.
     *
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function createAvailableCourse(array $data): AvailableCourse
    {
        return DB::transaction(function () use ($data) {
            $this->validateAvailableCourseData($data);
            $this->ensureAvailableCourseDoesNotExist($data);
            
            $isUniversal = $data['is_universal'] ?? false;
            $availableCourse = $this->createAvailableCourseRecord($data);
            
            if (!$isUniversal) {
                $eligibility = $data['eligibility'] ?? [];
                $this->attachEligibilities($availableCourse, $eligibility);
            }
            
            return $availableCourse->fresh(['programs', 'levels']);
        });
    }

    /**
     * Update an existing available course with transaction safety.
     *
     * @param AvailableCourse $availableCourse
     * @param array $data
     * @return AvailableCourse
     * @throws BusinessValidationException
     */
    public function updateAvailableCourse(AvailableCourse $availableCourse, array $data): AvailableCourse
    {
        return DB::transaction(function () use ($availableCourse, $data) {
            $this->validateAvailableCourseData($data);
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
     * Get available course by ID with eligibilities.
     *
     * @param int $id
     * @return AvailableCourse
     * @throws ModelNotFoundException
     */
    public function getAvailableCourseWithEligibilities(int $id): AvailableCourse
    {
        return AvailableCourse::with(['eligibilities.program', 'eligibilities.level', 'course', 'term'])
            ->findOrFail($id);
    }

    /**
     * Get formatted available course data for frontend.
     *
     * @param int $id
     * @return array
     * @throws ModelNotFoundException
     */
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

    /**
     * Update available course by ID.
     *
     * @param int $id
     * @param array $data
     * @return AvailableCourse
     * @throws ModelNotFoundException
     */
    public function updateAvailableCourseById(int $id, array $data): AvailableCourse
    {
        $availableCourse = AvailableCourse::findOrFail($id);
        return $this->updateAvailableCourse($availableCourse, $data);
    }

    /**
     * Get DataTables JSON response for available courses.
     *
     * @return JsonResponse
     */
    public function getDatatable(): JsonResponse
    {
        $query = AvailableCourse::with(['course', 'term', 'eligibilities.program', 'eligibilities.level']);
        
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
            ->make(true);
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
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // Account for header row and 0-based index
            
            try {
                DB::transaction(function () use ($row, $rowNum, &$created) {
                    $this->processImportRow($row->toArray(), $rowNum);
                    $created++;
                });
            } catch (ValidationException $e) {
                $errors[] = "Row {$rowNum}: " . implode(', ', $e->errors()["Row {$rowNum}"] ?? []);
            } catch (BusinessValidationException $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNum}: Unexpected error - " . $e->getMessage();
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
        
        return [
            'success' => empty($errors),
            'message' => empty($errors) 
                ? "Successfully imported {$created} available courses." 
                : "Import completed with {$created} successful and " . count($errors) . " failed rows.",
            'errors' => $errors,
            'created' => $created,
        ];
    }

    /**
     * Process a single import row.
     *
     * @param array $row
     * @param int $rowNum
     * @throws ValidationException|BusinessValidationException
     */
    private function processImportRow(array $row, int $rowNum): void
    {
        // Validate row structure
        AvailableCourseImportValidator::validateRow($row, $rowNum);
        
        // Find related models
        $course = $this->findCourseByCode($row['course_code'] ?? '');
        $term = $this->findTermByCode($row['term_code'] ?? '');
        $program = $this->findProgramByName($row['program_name'] ?? '');
        $level = $this->findLevelByName($row['level_name'] ?? '');
        
        // Check for existing course with same eligibility
        $this->checkForDuplicateImportCourse($course, $term, $program, $level);
        
        // Create available course
        $availableCourse = AvailableCourse::create([
            'course_id' => $course->id,
            'term_id' => $term->id,
            'min_capacity' => $row['min_capacity'] ?? 1,
            'max_capacity' => $row['max_capacity'] ?? 30,
            'is_universal' => false,
        ]);
        
        // Create eligibility
        CourseEligibility::create([
            'available_course_id' => $availableCourse->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
        ]);
    }

     /**
     * Find course by code.
     *
     * @param string $code
     * @return Course
     * @throws BusinessValidationException
     */
    private function findCourseByCode(string $code): Course
    {
        $course = Course::where('code', $code)->first();
        
        if (!$course) {
            throw new BusinessValidationException("Course with code '{$code}' not found.");
        }
        
        return $course;
    }

    /**
     * Find term by code.
     *
     * @param string $code
     * @return Term
     * @throws BusinessValidationException
     */
    private function findTermByCode(string $code): Term
    {
        $term = Term::whereRaw(
            "CONCAT(LOWER(season), year) = ? OR CONCAT(year, LOWER(season)) = ?",
            [strtolower($code), strtolower($code)]
        )->first();
        
        if (!$term) {
            throw new BusinessValidationException("Term with code '{$code}' not found.");
        }
        
        return $term;
    }

    /**
     * Find program by name.
     *
     * @param string $name
     * @return Program
     * @throws BusinessValidationException
     */
    private function findProgramByName(string $name): Program
    {
        $program = Program::where('name', $name)->first();
        
        if (!$program) {
            throw new BusinessValidationException("Program '{$name}' not found.");
        }
        
        return $program;
    }

    /**
     * Find level by name.
     *
     * @param string $name
     * @return Level
     * @throws BusinessValidationException
     */
    private function findLevelByName(string $name): Level
    {
        $level = Level::where('name', $name)->first();
        
        if (!$level) {
            throw new BusinessValidationException("Level '{$name}' not found.");
        }
        
        return $level;
    }

    /**
     * Validate available course data.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
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

    /**
     * Ensure available course uniqueness constraints.
     *
     * @param array $data
     * @param int|null $excludeId
     * @throws BusinessValidationException
     */
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

    /**
     * Check if universal available course exists.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
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
     * Check if available course with same eligibilities exists.
     *
     * @param array $data
     * @param int|null $excludeId
     * @return bool
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
     * Create available course record.
     *
     * @param array $data
     * @return AvailableCourse
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
     * Attach eligibilities to available course.
     *
     * @param AvailableCourse $availableCourse
     * @param array $eligibility
     */
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