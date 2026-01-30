<?php

declare(strict_types=1);

namespace App\Services\Student\Operations;

use App\Models\Student;
use App\Models\Level;
use App\Models\Program;
use App\Validators\StudentImportValidator;
use App\Exceptions\BusinessValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessImportService
{
    // Column indices (0-based, matching Excel columns)
    private const NATIONAL_ID_COLUMN = 0;
    private const ACADEMIC_ID_COLUMN = 1;
    private const NAME_EN_COLUMN = 2;
    private const NAME_AR_COLUMN = 3;
    private const ACADEMIC_EMAIL_COLUMN = 4;
    private const LEVEL_COLUMN = 5;
    private const PROGRAM_COLUMN = 6;
    private const CGPA_COLUMN = 7;
    private const TAKEN_CREDIT_HOURS_COLUMN = 8;

    protected array $results = [
        'summary' => [
            'total_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
        ],
        'rows' => [],
    ];

    public function __construct(
        protected array $rows
    ) {}

    /**
     * Main entry point for processing students
     */
    public function process(): array
    {
        try {
            if (empty($this->rows)) {
                return $this->results;
            }

            $this->processRows();

            return $this->results;

        } catch (Throwable $e) {
            Log::error('Student processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process all rows
     */
    private function processRows(): void
    {
        foreach ($this->rows as $index => $row) {
            $rowNum = $index + 2; // Excel rows are 1-indexed, plus header row

            try {
                DB::transaction(function () use ($row, $rowNum) {
                    $this->processSingleRow($row, $rowNum);
                });

                $this->results['summary']['total_processed']++;

            } catch (ValidationException $e) {
                $this->handleRowError($rowNum, $e->errors(), $row);
            } catch (BusinessValidationException $e) {
                $this->handleRowError($rowNum, ['general' => [$e->getMessage()]], $row);
            } catch (Throwable $e) {
                $this->handleRowError($rowNum, ['general' => ['Unexpected error: ' . $e->getMessage()]], $row);
                Log::error('Import row processing failed', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
    }

    /**
     * Process a single row
     */
    private function processSingleRow(array $row, int $rowNum): void
    {
        $this->validateRow($row, $rowNum);

        $level = $this->findLevelByName($row[self::LEVEL_COLUMN] ?? '');
        $program = $this->findProgramByName($row[self::PROGRAM_COLUMN] ?? '');
        $gender = $this->extractGenderFromNationalId($row[self::NATIONAL_ID_COLUMN] ?? '');

        $student = $this->createOrUpdateStudent($row, $level, $program, $gender);

        // Track success
        if ($student->wasRecentlyCreated) {
            $this->results['summary']['created']++;
        } else {
            $this->results['summary']['updated']++;
        }
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row, int $rowNum): void
    {
        $validator = Validator::make($row, [
            self::NATIONAL_ID_COLUMN => 'required|string|size:14',
            self::ACADEMIC_ID_COLUMN => 'required|string',
            self::NAME_EN_COLUMN => 'required|string|max:255',
            self::NAME_AR_COLUMN => 'nullable|string|max:255',
            self::ACADEMIC_EMAIL_COLUMN => 'required|email|max:255',
            self::LEVEL_COLUMN => 'required|string',
            self::PROGRAM_COLUMN => 'required|string',
            self::CGPA_COLUMN => 'nullable|numeric|between:0,4',
            self::TAKEN_CREDIT_HOURS_COLUMN => 'nullable|integer|min:0',
        ], [
            self::NATIONAL_ID_COLUMN . '.required' => 'National ID is required',
            self::NATIONAL_ID_COLUMN . '.size' => 'National ID must be 14 digits',
            self::ACADEMIC_ID_COLUMN . '.required' => 'Academic ID is required',
            self::NAME_EN_COLUMN . '.required' => 'English name is required',
            self::ACADEMIC_EMAIL_COLUMN . '.required' => 'Academic email is required',
            self::ACADEMIC_EMAIL_COLUMN . '.email' => 'Academic email must be valid',
            self::LEVEL_COLUMN . '.required' => 'Level is required',
            self::PROGRAM_COLUMN . '.required' => 'Program name is required',
            self::CGPA_COLUMN . '.between' => 'CGPA must be between 0 and 4',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Find level by name
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
     * Find program by name
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
     * Extract gender from Egyptian national ID
     */
    private function extractGenderFromNationalId($nationalId): ?string
    {
        $nationalId = (string)$nationalId;
        
        // Validate Egyptian national ID format (14 digits)
        if (!preg_match('/^\d{14}$/', $nationalId)) {
            return null;
        }
        
        // The 13th digit (index 12, 0-based) determines gender: odd = male, even = female
        $genderDigit = (int)substr($nationalId, 12, 1);
        
        return ($genderDigit % 2 === 0) ? 'female' : 'male';
    }

    /**
     * Create or update student
     */
    private function createOrUpdateStudent(array $row, Level $level, Program $program, ?string $gender): Student
    {
        return Student::updateOrCreate(
            ['national_id' => (string)($row[self::NATIONAL_ID_COLUMN] ?? '')],
            [
                'name_en' => (string)($row[self::NAME_EN_COLUMN] ?? ''),
                'name_ar' => !empty(trim($row[self::NAME_AR_COLUMN] ?? '')) ? (string)($row[self::NAME_AR_COLUMN]) : null,
                'academic_id' => (string)($row[self::ACADEMIC_ID_COLUMN] ?? ''),
                'academic_email' => (string)($row[self::ACADEMIC_EMAIL_COLUMN] ?? ''),
                'level_id' => $level->id,
                'cgpa' => $row[self::CGPA_COLUMN] ?? 0,
                'program_id' => $program->id,
                'gender' => $gender,
                'taken_credit_hours' => $row[self::TAKEN_CREDIT_HOURS_COLUMN] ?? 0,
            ]
        );
    }

    /**
     * Handle row error
     */
    private function handleRowError(int $rowNum, array $errors, array $originalData): void
    {
        $this->results['summary']['failed']++;

        $this->results['rows'][] = [
            'row' => $rowNum,
            'errors' => $errors,
            'original_data' => $originalData,
        ];
    }
}
