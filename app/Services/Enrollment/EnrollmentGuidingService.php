<?php

namespace App\Services\Enrollment;

use App\Models\Course;
use App\Models\CurriculumElectiveCourse;
use App\Models\CurriculumElectiveGroup;
use App\Models\ElectiveCourse;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudyPlan;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EnrollmentGuidingService
{
    private const PASSING_GRADES = ['A+','A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'P'];
    
    private int $studentLvl;
    private int $semesterNo;
    private int $programId;

    private array $coursesHistory = [
        'passed_courses' => [],
        'failed_courses' => [],
        'incomplete_courses' => [],
    ];

    public function __construct(
        private readonly int $studentId,
        private readonly ?int $termId = null
    ) {}

    public function guide(): array
    {
        try {
            $this->assignStudentInfo();

            $this->coursesHistory = $this->getStudentCoursesHistory();

            $studyPlanCourses = $this->getStudyPlanCourses();

            $result = [
                'courses_history' => $this->coursesHistory,
                'study_plan_courses' => $studyPlanCourses,
                'missing_courses' => $this->getMissingCourses(),
                'student_level' => $this->studentLvl,
                'semester_no' => $this->semesterNo,
            ];

            return $result;
        } catch (\Exception $e) {
            Log::error("Error in enrollment guiding for student ID {$this->studentId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign student information from database
     */
    private function assignStudentInfo(): void
    {
        $student = Student::with(['program', 'level'])
            ->findOrFail($this->studentId);

        $this->studentLvl = $student->level->id;
        $this->semesterNo = $this->determineSemesterNo($student->level->id);
        $this->programId = $student->program->id;
    }

    /**
     * Determine semester number based on student level and selected term
     */
    private function determineSemesterNo(int $level): int
    {
        // If no term is selected, or it's a Summer term, default to the "standard" even semester for that level
        if (!$this->termId) {
            return $level * 2;
        }

        $term = \App\Models\Term::find($this->termId);
        if (!$term) {
            return $level * 2;
        }

        $season = strtolower($term->season);

        // Fall semester (Odd): 1, 3, 5, 7
        // Spring semester (Even): 2, 4, 6, 8
        if ($season === 'fall') {
            return ($level * 2) - 1;
        } elseif ($season === 'spring') {
            return $level * 2;
        }

        return $level * 2;
    }

    /**
     * Get student's course history categorized by status
     */
    private function getStudentCoursesHistory(): array
    {
        $coursesHistory = [
            'passed_courses' => [],
            'failed_courses' => [],
            'incomplete_courses' => [],
        ];

        $enrollments = Enrollment::with(['course'])
            ->where('student_id', $this->studentId)
            ->get();

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            
            if (!$course) {
                continue; 
            }

            $entry = [
                'course' => $course,
                'grade' => $enrollment->grade
            ];

            if ($enrollment->grade === null) {
                $coursesHistory['incomplete_courses'][] = $entry;
            } elseif (in_array($enrollment->grade, self::PASSING_GRADES, true)) {
                $coursesHistory['passed_courses'][] = $entry;
            } else {
                $coursesHistory['failed_courses'][] = $entry;
            }
        }

        return $coursesHistory;
    }

    /**
     * Get study plan courses for current semester with availability check
     */
    private function getStudyPlanCourses(): array
    {
        $studyPlans = StudyPlan::with(['course.prerequisites', 'electiveCourse'])
            ->where('program_id', $this->programId)
            ->where('semester_no', $this->semesterNo)
            ->get();
        
        $passedCourseIds = collect($this->coursesHistory['passed_courses'])
            ->pluck('course.id')
            ->toArray();
            
        $incompleteCourseIds = collect($this->coursesHistory['incomplete_courses'])
            ->pluck('course.id')
            ->toArray();

        $courses = [];
        $electiveSlotCount = 0;
        $electiveSlotCodes = [];
        $allElectiveCoursePool = [];

        foreach ($studyPlans as $studyPlan) {
            if ($studyPlan->course) {
                $available = $this->arePrerequisitesMet($studyPlan->course, $passedCourseIds);
                $courses[] = [
                    'course' => $studyPlan->course,
                    'available' => $available,
                    'is_passed' => in_array($studyPlan->course->id, $passedCourseIds),
                    'is_incomplete' => in_array($studyPlan->course->id, $incompleteCourseIds),
                    'reason' => $available ? null : 'Prerequisites not met'
                ];
            } elseif ($studyPlan->electiveCourse) {
                $electiveSlotCount++;
                $electiveSlotCodes[] = $studyPlan->electiveCourse->code;
                
                // Fetch all courses in this elective's pool
                $pool = $this->getElectivePoolForGroup($studyPlan->electiveCourse->id);
                foreach ($pool as $courseId => $course) {
                    $allElectiveCoursePool[$courseId] = $course;
                }
            }
        }

        // Format and sort the elective pool
        $formattedPool = [];
        foreach ($allElectiveCoursePool as $courseId => $course) {
            $available = $this->arePrerequisitesMet($course, $passedCourseIds);
            $isTaken = in_array($courseId, $passedCourseIds) || in_array($courseId, $incompleteCourseIds);
            
            $formattedPool[] = [
                'course' => $course,
                'available' => $available,
                'is_passed' => in_array($courseId, $passedCourseIds),
                'is_incomplete' => in_array($courseId, $incompleteCourseIds),
                'is_taken' => $isTaken,
                'reason' => $available ? null : 'Prerequisites not met'
            ];
        }

        usort($formattedPool, function($a, $b) {
            $scoreA = ($a['is_taken'] ? 3 : ($a['available'] ? 1 : 2));
            $scoreB = ($b['is_taken'] ? 3 : ($b['available'] ? 1 : 2));
            return $scoreA <=> $scoreB;
        });

        return [
            'courses' => $courses,
            'elective_info' => [
                'count' => $electiveSlotCount,
                'codes' => array_values(array_unique($electiveSlotCodes)),
                'pool' => $formattedPool
            ],
            'semester_no' => $this->semesterNo,
        ];
    }

    /**
     * Check if all prerequisites for a course are met
     */
    private function arePrerequisitesMet(Course $course, array $passedCourseIds): bool
    {
        $prerequisiteIds = $course->prerequisites->pluck('id')->toArray();
        
        if (empty($prerequisiteIds)) {
            return true;
        }

        return collect($prerequisiteIds)->every(
            fn($id) => in_array($id, $passedCourseIds, true)
        );
    }

    /**
     * Get courses history
     */
    public function getCoursesHistory(): array
    {
        return $this->coursesHistory;
    }

    /**
     * Get missing courses from previous semesters
     */
    private function getMissingCourses(): array
    {
        $previousPlans = StudyPlan::with(['course.prerequisites', 'electiveCourse'])
            ->where('program_id', $this->programId)
            ->where('semester_no', '<', $this->semesterNo)
            ->orderBy('semester_no')
            ->get();

        $passedCourseIds = collect($this->coursesHistory['passed_courses'])
            ->pluck('course.id')
            ->toArray();
            
        $incompleteCourseIds = collect($this->coursesHistory['incomplete_courses'])
            ->pluck('course.id')
            ->toArray();

        $missingCore = [];
        $electiveRequirements = []; 

        foreach ($previousPlans as $plan) {
            if ($plan->course) {
                if (!in_array($plan->course->id, $passedCourseIds)) {
                    $available = $this->arePrerequisitesMet($plan->course, $passedCourseIds);
                    $isTaken = in_array($plan->course->id, $incompleteCourseIds); // Incomplete means taken but not passed yet
                    
                    $missingCore[] = [
                        'course' => $plan->course,
                        'semester' => $plan->semester_no,
                        'available' => $available,
                        'is_incomplete' => $isTaken, // To distinguish "Never Taken" vs "Failed/Incomplete"
                        'reason' => $available ? null : 'Prerequisites not met'
                    ];
                }
            } elseif ($plan->electiveCourse) {
                 $groupId = $plan->electiveCourse->id;
                 $code = $plan->electiveCourse->code;
                 if (!isset($electiveRequirements[$groupId])) {
                     $electiveRequirements[$groupId] = ['count' => 0, 'code' => $code];
                 }
                 $electiveRequirements[$groupId]['count']++;
            }
        }

        // Process Electives
        $missingElectiveData = [
            'count' => 0,
            'codes' => [],
            'pool' => []
        ];
        
        $masterPool = [];

        foreach ($electiveRequirements as $groupId => $data) {
             $requiredCount = $data['count'];
             $code = $data['code'];
             
             // Get pool for this group
             $pool = $this->getElectivePoolForGroup($groupId); 
             
             // Count how many from this pool are passed
             $passedCount = 0;
             foreach ($pool as $course) {
                 if (in_array($course->id, $passedCourseIds)) {
                     $passedCount++;
                 }
             }
             
             $missingCount = max(0, $requiredCount - $passedCount);
             
             if ($missingCount > 0) {
                 $missingElectiveData['count'] += $missingCount;
                 $missingElectiveData['codes'][] = $code;

                 // Add available courses to master pool
                 foreach ($pool as $course) {
                     // Check if already in master pool to avoid duplicates
                     if (isset($masterPool[$course->id])) continue;
                     
                     // If passed, don't add to available pool (we are looking for what to take)
                     // If incomplete, it IS available to retake, so add it.
                     if (in_array($course->id, $passedCourseIds)) continue;

                     $available = $this->arePrerequisitesMet($course, $passedCourseIds);
                     $isTaken = in_array($course->id, $incompleteCourseIds);

                     $masterPool[$course->id] = [
                         'course' => $course,
                         'available' => $available,
                         'is_incomplete' => $isTaken,
                         'is_taken' => $isTaken, // For sorting consistency
                         'is_passed' => false,
                         'reason' => $available ? null : 'Prerequisites not met'
                     ];
                 }
             }
        }

        $missingElectiveData['codes'] = array_values(array_unique($missingElectiveData['codes']));
        $missingElectiveData['pool'] = array_values($masterPool);

        // Sort pool
        usort($missingElectiveData['pool'], function($a, $b) {
            $scoreA = ($a['is_taken'] ? 3 : ($a['available'] ? 1 : 2));
            $scoreB = ($b['is_taken'] ? 3 : ($b['available'] ? 1 : 2));
            return $scoreA <=> $scoreB;
        });

        return [
            'core' => $missingCore,
            'electives' => $missingElectiveData
        ];
    }

    /**
     * Helper to get elective pool for a specific group ID
     */
    private function getElectivePoolForGroup(int $groupId): array
    {
        $curriculumGroups = CurriculumElectiveGroup::with(['courses.course.prerequisites'])
            ->where('program_id', $this->programId)
            ->whereHas('groupSet.electives', function ($q) use ($groupId) {
                $q->where('elective_group_id', $groupId);
            })
            ->get();

        $pool = [];
        foreach ($curriculumGroups as $group) {
            foreach ($group->courses as $curriculumCourse) {
                if ($curriculumCourse->course) {
                    $pool[$curriculumCourse->course->id] = $curriculumCourse->course;
                }
            }
        }
        return $pool;
    }
}
