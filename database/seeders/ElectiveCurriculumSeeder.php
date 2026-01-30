<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Program;
use App\Models\ElectiveCourse;
use App\Models\ElectiveGroupSet;
use App\Models\ElectiveGroupSetItem;
use App\Models\CurriculumElectiveGroup;
use App\Models\CurriculumElectiveCourse;
use App\Models\Course;

class ElectiveCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            $electives = $this->createElectiveCodes();
            $programsData = $this->getProgramsData();
            $this->seedProgramElectives($programsData, $electives);
            
            DB::commit();
            $this->command->info('Elective curriculum seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create elective codes E1-E8
     */
    private function createElectiveCodes(): array
    {
        $electives = [];
        
        foreach (range(1, 8) as $i) {
            $code = 'E' . $i;
            $electives[$code] = ElectiveCourse::firstOrCreate(
                ['code' => $code],
                ['name' => 'Elective ' . $i]
            );
        }
        
        return $electives;
    }

    /**
     * Get programs data structure
     */
    private function getProgramsData(): array
    {
        return [
            'Computer Engineering' => [
                'groups' => [
                    'E1–E2' => [
                        'ELE211', 'ELE331', 'ELE232', 'CHE142',
                        'MAT121', 'MAT122', 'MAT231'
                    ],
                    'E3-E8' => [
                        'CSE271','CSE273','CSE281','CSE311','CSE376',
                        'CSE382','CSE383','CSE425','CSE448','AIE322',
                        'AIE323','AIE332','AIE351'
                    ]
                ]
            ],

            'Artificial Intelligence Engineering' => [
                'groups' => [
                    'E1–E2' => [
                        'ELE211', 'ELE331', 'ELE232', 'CHE142',
                        'MAT121', 'MAT122', 'MAT231'
                    ],
                    'E3–E8' => [
                        'CSE273', 'CSE322', 'CSE363', 'CSE376', 'CSE382',
                        'CSE446', 'CSE464', 'CSE478', 'CSE485', 'CSE487',
                        'CSE488', 'AIE315', 'AIE316', 'AIE342', 'AIE343',
                        'AIE417', 'AIE418', 'AIE426', 'AIE427', 'AIE444',
                        'AIE452', 'AIE453', 'AIE454', 'AIE455', 'AIE456',
                        'AIE457', 'AIE419', 'AIE314', 'AIE424'
                    ]
                ]
            ],

            'Computer Science' => [
                'groups' => [
                    'E1–E3' => [
                        'ELE432', 'CSE271', 'CSE272', 'CSE281', 'CSE322',
                        'CSE344', 'CSE424', 'CSE426', 'CSE453', 'CSE455',
                        'CSE464', 'CSE478', 'AIE231', 'AIE241', 'AIE314',
                        'AIE322', 'AIE332', 'AIE342', 'AIE343', 'AIE424',
                        'AIE425', 'CSE467'
                    ]
                ]
            ],

            'Artificial Intelligence Science' => [
                'groups' => [
                    'E1–E3' => [
                        'ELE432', 'CSE212', 'CSE272', 'CSE322', 'CSE382',
                        'CSE383', 'CSE464', 'CSE478', 'CSE485', 'CSE487',
                        'CSE488', 'AIE314', 'AIE315', 'AIE316', 'AIE342',
                        'CSE363', 'AIE417', 'AIE418', 'AIE426', 'AIE427',
                        'AIE444', 'AIE457', 'AIE419', 'CSE467', 'AIE343'
                    ]
                ]
            ],

            'Biomedical Informatics' => [
                'groups' => [
                    'E1–E5' => [
                        'ELE113', 'ELE432', 'CSE132', 'CSE211', 'CSE212',
                        'CSE233', 'CSE241', 'CSE272', 'CSE273', 'CSE322',
                        'CSE363', 'BMD431', 'CSE446', 'CSE453', 'CSE464',
                        'CSE478', 'CSE484', 'BMD312', 'AIE231', 'AIE241',
                        'AIE323', 'AIE342', 'AIE343', 'AIE424', 'AIE425',
                        'BMD414', 'BMD415', 'BMD422', 'BMD452', 'BMD462',
                        'BMD463', 'CSE467'
                    ]
                ]
            ],
        ];
    }

    /**
     * Seed program electives
     */
    private function seedProgramElectives(array $programsData, array $electives): void
    {
        foreach ($programsData as $programName => $programData) {
            $program = $this->getProgram($programName);
            
            if (!$program) {
                $this->command->warn("Program not found: {$programName}");
                continue;
            }

            $this->seedProgramGroups($program, $programData['groups'], $electives);
        }
    }

    /**
     * Get program from database
     */
    private function getProgram(string $programName): ?Program
    {
        return Program::where('name', $programName)->first();
    }

    /**
     * Seed groups for a program
     */
    private function seedProgramGroups(Program $program, array $groups, array $electives): void
    {
        foreach ($groups as $groupName => $courseCodes) {
            $groupSet = $this->createElectiveGroupSet($groupName, $electives);
            $curriculumGroup = $this->createCurriculumGroup($program, $groupSet);
            $this->attachCoursesToGroup($curriculumGroup, $courseCodes);
        }
    }

    /**
     * Create or get elective group set
     */
    private function createElectiveGroupSet(string $groupName, array $electives): ElectiveGroupSet
    {
        $groupSet = ElectiveGroupSet::firstOrCreate(['name' => $groupName]);
        
        // Extract elective codes (E1, E2, etc.) from group name
        preg_match_all('/E\d+/', $groupName, $matches);
        
        foreach ($matches[0] as $code) {
            ElectiveGroupSetItem::firstOrCreate([
                'elective_group_set_id' => $groupSet->id,
                'elective_group_id' => $electives[$code]->id
            ]);
        }
        
        return $groupSet;
    }

    /**
     * Create curriculum elective group
     */
    private function createCurriculumGroup(Program $program, ElectiveGroupSet $groupSet): CurriculumElectiveGroup
    {
        return CurriculumElectiveGroup::firstOrCreate([
            'program_id' => $program->id,
            'elective_group_set_id' => $groupSet->id
        ]);
    }

    /**
     * Attach courses to curriculum group
     */
    private function attachCoursesToGroup(CurriculumElectiveGroup $curriculumGroup, array $courseCodes): void
    {
        foreach ($courseCodes as $courseCode) {
            $course = $this->getCourse($courseCode);
            
            if (!$course) {
                $this->command->warn("Course not found: {$courseCode}");
                continue;
            }

            CurriculumElectiveCourse::firstOrCreate([
                'curriculum_elective_group_id' => $curriculumGroup->id,
                'course_id' => $course->id
            ]);
        }
    }

    /**
     * Get course from database
     */
    private function getCourse(string $courseCode): ?Course
    {
        return Course::where('code', $courseCode)->first();
    }
}