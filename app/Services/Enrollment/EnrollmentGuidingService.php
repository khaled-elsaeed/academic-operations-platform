<?php

namespace App\Services\Enrollment;

use App\Models\Course;
use App\Models\CurriculumElectiveCourse;
use App\Models\CurriculumElectiveGroup;
use App\Models\ElectiveCourse;
use App\Models\ElectiveGroupSetItem;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudyPlan;
use App\Models\UniversityRequirement;
use App\Models\UniversityRequirementGroupSet;
use App\Models\UniversityRequirementGroupSetItem;
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
        $studyPlans = StudyPlan::with(['course.prerequisites', 'electiveCourse', 'universityRequirement.course', 'universityRequirement.groupSet.courses.prerequisites'])
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
        $courses = [];
        
        // Program Electives (Original E1, E2...)
        $electiveSlotCount = 0;
        $electiveSlotCodes = [];
        $processedElectiveGroupSets = [];
        $electiveMasterPool = [];

        // University Requirements (Pool-based / Compulsory type)
        $urSlotCount = 0;
        $urSlotCodes = [];
        $processedUrGroupSets = [];
        $urMasterPool = [];

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
                
                $groupSetId = $this->getGroupSetIdForElective($studyPlan->electiveCourse->id);
                if ($groupSetId && !isset($processedElectiveGroupSets[$groupSetId])) {
                    $processedElectiveGroupSets[$groupSetId] = true;
                    $pool = $this->getElectivePoolForGroupSet($groupSetId);
                    foreach ($pool as $courseId => $course) {
                        $electiveMasterPool[$courseId] = $course;
                    }
                }
            } elseif ($studyPlan->universityRequirement) {
                $req = $studyPlan->universityRequirement;
                
                if ($req->type === 'elective') {
                    if ($req->course) {
                         $available = $this->arePrerequisitesMet($req->course, $passedCourseIds);
                         $courses[] = [
                            'course' => $req->course,
                            'available' => $available,
                            'is_passed' => in_array($req->course->id, $passedCourseIds),
                            'is_incomplete' => in_array($req->course->id, $incompleteCourseIds),
                            'reason' => $available ? null : 'Prerequisites not met'
                         ];
                    }
                } elseif ($req->type === 'compulsory') {
                    $groupSet = $req->groupSet;                     
                    if ($groupSet) {
                        $urSlotCount++; 
                        $urSlotCodes[] = $req->code;
                        
                        $gsId = 'UR_' . $groupSet->id; 
                        
                        if (!isset($processedUrGroupSets[$gsId])) {
                            $processedUrGroupSets[$gsId] = true;
                           if ($groupSet->courses) {
                               foreach ($groupSet->courses as $course) {
                                   $urMasterPool[$course->id] = $course;
                               }
                           }
                        }
                    }
                }
            }
        }

        // Format and sort Program Electives Pool
        $formattedElectivePool = $this->formatAndSortPool($electiveMasterPool, $passedCourseIds, $incompleteCourseIds);

        // Format and sort University Requirements Pool
        $formattedUrPool = $this->formatAndSortPool($urMasterPool, $passedCourseIds, $incompleteCourseIds);

        return [
            'courses' => $courses,
            'elective_info' => [
                'count' => $electiveSlotCount,
                'codes' => array_values(array_unique($electiveSlotCodes)),
                'pool' => $formattedElectivePool
            ],
            'university_req_info' => [
                'count' => $urSlotCount,
                'codes' => array_values(array_unique($urSlotCodes)),
                'pool' => $formattedUrPool
            ],
            'semester_no' => $this->semesterNo,
        ];
    }

    private function formatAndSortPool(array $masterPool, array $passedCourseIds, array $incompleteCourseIds): array
    {
        $formattedPool = [];
        foreach ($masterPool as $courseId => $course) {
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
        
        return $formattedPool;
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
        $previousPlans = StudyPlan::with(['course.prerequisites', 'electiveCourse', 'universityRequirement.course', 'universityRequirement.groupSet.courses.prerequisites'])
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
        $urRequirements = [];
        $processedGroupSets = [];

        foreach ($previousPlans as $plan) {
            if ($plan->course) {
                if (!in_array($plan->course->id, $passedCourseIds)) {
                    $available = $this->arePrerequisitesMet($plan->course, $passedCourseIds);
                    $isTaken = in_array($plan->course->id, $incompleteCourseIds);
                    
                    $missingCore[] = [
                        'course' => $plan->course,
                        'semester' => $plan->semester_no,
                        'available' => $available,
                        'is_incomplete' => $isTaken,
                        'reason' => $available ? null : 'Prerequisites not met'
                    ];
                }
            } elseif ($plan->electiveCourse) {
                 $electiveId = $plan->electiveCourse->id;
                 $code = $plan->electiveCourse->code;
                 $groupSetId = $this->getGroupSetIdForElective($electiveId);
                 
                 // Track requirements by group set ID to consolidate
                 if ($groupSetId) {
                     if (!isset($electiveRequirements[$groupSetId])) {
                         $electiveRequirements[$groupSetId] = ['count' => 0, 'codes' => []];
                     }
                     $electiveRequirements[$groupSetId]['count']++;
                     $electiveRequirements[$groupSetId]['codes'][] = $code;
                 }
            } elseif ($plan->universityRequirement) {
                $req = $plan->universityRequirement;
                
                if ($req->type === 'elective') {
                    if ($req->course && !in_array($req->course->id, $passedCourseIds)) {
                         $available = $this->arePrerequisitesMet($req->course, $passedCourseIds);
                         $isTaken = in_array($req->course->id, $incompleteCourseIds);
                         $missingCore[] = [
                            'course' => $req->course,
                            'semester' => $plan->semester_no,
                            'available' => $available,
                            'is_incomplete' => $isTaken,
                            'reason' => $available ? null : 'Prerequisites not met'
                         ];
                    }
                } elseif ($req->type === 'compulsory') {
                     $groupSet = $req->groupSet;
                     if ($groupSet) {
                         $gsId = 'UR_' . $groupSet->id;
                         if (!isset($urRequirements[$gsId])) {
                              $urRequirements[$gsId] = ['count' => 0, 'codes' => [], 'group_set' => $groupSet];
                         }
                         $urRequirements[$gsId]['count']++;
                         $urRequirements[$gsId]['codes'][] = $req->code;
                     }
                }
            }
        }

        $missingElectiveData = $this->processMissingPoolRequirements($electiveRequirements, $passedCourseIds, $incompleteCourseIds, false);

        $missingUrData = $this->processMissingPoolRequirements($urRequirements, $passedCourseIds, $incompleteCourseIds, true);

        return [
            'core' => $missingCore,
            'electives' => $missingElectiveData,
            'university_reqs' => $missingUrData
        ];
    }

    private function processMissingPoolRequirements(array $requirements, array $passedCourseIds, array $incompleteCourseIds, bool $isUr): array
    {
        $missingData = [
            'count' => 0,
            'codes' => [],
            'pool' => []
        ];
        
        $masterPool = [];

        foreach ($requirements as $groupSetId => $data) {
             $requiredCount = $data['count'];
             $codes = $data['codes'];
             
             if ($isUr) {
                 $pool = $data['group_set']->courses ?? [];
             } else {
                 $pool = $this->getElectivePoolForGroupSet($groupSetId);
             }
             
             // Count how many from this pool are passed
             $passedCount = 0;
             foreach ($pool as $course) {
                 if (in_array($course->id, $passedCourseIds)) {
                     $passedCount++;
                 }
             }
             
             $missingCount = max(0, $requiredCount - $passedCount);
             
             if ($missingCount > 0) {
                 $missingData['count'] += $missingCount;
                 $missingData['codes'] = array_merge($missingData['codes'], $codes);

                 // Add available courses to master pool
                 foreach ($pool as $course) {
                     if (isset($masterPool[$course->id])) continue;
                     if (in_array($course->id, $passedCourseIds)) continue;

                     $available = $this->arePrerequisitesMet($course, $passedCourseIds);
                     $isTaken = in_array($course->id, $incompleteCourseIds);

                     $masterPool[$course->id] = $course;
                 }
             }
        }

        $missingData['codes'] = array_values(array_unique($missingData['codes']));
        $missingData['pool'] = $this->formatAndSortPool($masterPool, $passedCourseIds, $incompleteCourseIds);
        
        return $missingData;
    }
    

    /**
     * Get the ElectiveGroupSet ID for a given ElectiveCourse (e.g., E1, E2) that belongs to this program
     */
    private function getGroupSetIdForElective(int $electiveId): ?int
    {
        $setItems = ElectiveGroupSetItem::where('elective_group_id', $electiveId)->get();
        
        foreach ($setItems as $setItem) {
            $curriculumGroup = CurriculumElectiveGroup::where('program_id', $this->programId)
                ->where('elective_group_set_id', $setItem->elective_group_set_id)
                ->first();
            
            if ($curriculumGroup) {
                return $setItem->elective_group_set_id;
            }
        }
        
        return null;
    }

    /**
     * Get elective pool for a specific ElectiveGroupSet ID
     */
    private function getElectivePoolForGroupSet(int $groupSetId): array
    {
        $curriculumGroup = CurriculumElectiveGroup::with(['courses.course.prerequisites'])
            ->where('program_id', $this->programId)
            ->where('elective_group_set_id', $groupSetId)
            ->first();

        if (!$curriculumGroup) {
            return [];
        }

        $pool = [];
        foreach ($curriculumGroup->courses as $curriculumCourse) {
            if ($curriculumCourse->course) {
                $pool[$curriculumCourse->course->id] = $curriculumCourse->course;
            }
        }
        return $pool;
    }
}
