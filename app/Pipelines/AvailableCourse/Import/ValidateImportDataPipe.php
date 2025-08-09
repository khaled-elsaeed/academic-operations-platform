<?php

namespace App\Pipelines\AvailableCourse\Import;

use App\Models\Course;
use App\Models\Term;
use App\Models\Program;
use App\Models\Level;
use App\Exceptions\BusinessValidationException;
use App\Validators\AvailableCourseImportValidator;
use Closure;

class ValidateImportDataPipe
{
    /**
     * Handle the pipeline step for validating import data.
     *
     * @param array $data
     * @param Closure $next
     * @return mixed
     * @throws BusinessValidationException
     */
    public function handle(array $data, Closure $next)
    {
        $rowData = $data['row_data'];
        $rowNumber = $data['row_number'];
        
        \Log::info('Pipeline: Validating import data', [
            'row_number' => $rowNumber,
            'data' => $rowData
        ]);

        // Map and validate the row data
        $mappedData = $this->mapRowData($rowData);
        
        // Validate the mapped data
        AvailableCourseImportValidator::validateRow($mappedData, $rowNumber);
        
        // Find and validate related entities
        $course = $this->findCourseByCode($mappedData['course_code']);
        $term = $this->findTermByCode($mappedData['term_code']);
        $program = $this->findProgramByCode($mappedData['program_code']);
        $level = $this->findLevelByName($mappedData['level_name']);
        
        // Add validated data to pipeline
        $data['mapped_data'] = $mappedData;
        $data['course'] = $course;
        $data['term'] = $term;
        $data['program'] = $program;
        $data['level'] = $level;

        return $next($data);
    }

    /**
     * Map row data to standardized format.
     *
     * @param array $row
     * @return array
     */
    private function mapRowData(array $row): array
    {
        return [
            'course_code' => $row['course_code'] ?? '',
            'course_name' => $row['course_name'] ?? '',
            'term_code' => $row['term'] ?? '',
            'activity_type' => strtolower($row['activity_type'] ?? 'lecture'),
            'group' => (int)($row['grouping'] ?? 1),
            'day' => $row['day'] ?? null,
            'slot' => $row['slot'] ?? null,
            'time' => $row['time'] ?? null,
            'instructor' => $row['instructor'] ?? null,
            'location' => $row['location'] ?? null,
            'external' => $row['external'] ?? null,
            'level_name' => $row['level'] ?? null,
            'program_code' => $row['program'] ?? null,
            'min_capacity' => (int)($row['min_capacity'] ?? 1),
            'max_capacity' => (int)($row['max_capacity'] ?? 30),
            'schedule_code' => $row['schedule_code'] ?? null,
        ];
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
        $term = Term::where('code', $code)->first();
        if (!$term) {
            throw new BusinessValidationException("Term with code '{$code}' not found.");
        }
        return $term;
    }

    /**
     * Find program by code.
     *
     * @param string|null $code
     * @return Program|null
     * @throws BusinessValidationException
     */
    private function findProgramByCode(?string $code): ?Program
    {
        if (empty($code)) {
            return null;
        }
        
        $program = Program::where('code', $code)->first();
        if (!$program) {
            throw new BusinessValidationException("Program with code '{$code}' not found.");
        }
        return $program;
    }

    /**
     * Find level by name.
     *
     * @param string|null $name
     * @return Level|null
     * @throws BusinessValidationException
     */
    private function findLevelByName(?string $name): ?Level
    {
        if (empty($name)) {
            return null;
        }
        
        $level = Level::where('name', $name)->first();
        if (!$level) {
            throw new BusinessValidationException("Level with name '{$name}' not found.");
        }
        
        return $level;
    }
}
