<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\UniversityRequirement;
use App\Models\UniversityRequirementGroupSet;
use App\Models\UniversityRequirementGroupSetItem;
use App\Models\UniversityRequirementCourse;

class UniversityRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $requirements = $this->createRequirementCodes();
            $data = $this->getRequirementsData();
            $this->seedRequirements($data, $requirements);

            DB::commit();
            $this->command->info('University Requirements seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create requirement codes (UC1-UC7, UE1-UE3)
     */
    private function createRequirementCodes(): array
    {
        $requirements = [];

        foreach (range(1, 7) as $i) {
            $code = 'UC' . $i;
            $requirements[$code] = UniversityRequirement::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'University Compulsory ' . $i,
                    'type' => 'elective' 
                ]
            );
        }

        // UE: Elective in user terms (Pool in schema -> 'compulsory')
        foreach (range(1, 3) as $i) {
            $code = 'UE' . $i;
            $requirements[$code] = UniversityRequirement::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'University Elective ' . $i,
                    'type' => 'compulsory' // 'compulsory' type maps to group set/pool
                ]
            );
        }

        return $requirements;
    }

    /**
     * Get the data mapping provided by the user
     */
    private function getRequirementsData(): array
    {
        return [
            'singles' => [
                'UC1' => 'LAN011',
                'UC2' => 'LAN114',
                'UC3' => 'MGT301',
                'UC4' => 'LAN112',
                'UC5' => 'GEO217',
                'UC6' => 'PSC101',
                'UC7' => 'LIB116',
            ],
            'groups' => [
                'UE1-UE3' => [
                    'PSC102', 'PSC111', 'MGT101', 'ECO205', 'MGT102',
                    'LAN111', 'MEC013', 'LAN120', 'LAN130', 'LAN140',
                    'LAN150', 'DVA014', 'DVA221', 'LAN113', 'LAN115',
                    'PYS103', 'SOC105', 'SOC216', 'MGT201', 'ADL123',
                    'HNU110', 'GEO218', 'HIS111', 'HIS113', 'HIS112',
                    'ARC010', 'SOC107', 'PSC207', 'GEO216', 'PSC209',
                    'GEO114', 'CSE013', 'MEC014', 'CSE012'
                ]
            ]
        ];
    }

    /**
     * Seed requirements based on data
     */
    private function seedRequirements(array $data, array $requirements): void
    {
        foreach ($data['singles'] as $reqCode => $courseCode) {
            $this->linkSingleCourse($requirements[$reqCode], $courseCode);
        }

        // 2. Seed Group Sets (UE)
        foreach ($data['groups'] as $groupKey => $courseCodes) {
            if (preg_match('/(UE\d+)-(UE\d+)/', $groupKey, $matches)) {
                $groupSet = $this->createGroupSet('University Electives Pool');

                $targetReqs = ['UE1', 'UE2', 'UE3']; 
                
                foreach ($targetReqs as $code) {
                    if (isset($requirements[$code])) {
                        $this->linkRequirementToGroupSet($requirements[$code], $groupSet);
                    }
                }

                $this->attachCoursesToGroupSet($groupSet, $courseCodes);
            }
        }
    }

    /**
     * Link a single course to a requirement (Type: Elective)
     */
    private function linkSingleCourse(UniversityRequirement $requirement, string $courseCode): void
    {
        $course = $this->getCourse($courseCode);
        
        if ($course) {
            $requirement->update([
                'course_id' => $course->id
            ]);
        } else {
            $this->command->warn("Course not found for requirement {$requirement->code}: $courseCode");
        }
    }

    /**
     * Create or Get Group Set
     */
    private function createGroupSet(string $name): UniversityRequirementGroupSet
    {
        return UniversityRequirementGroupSet::firstOrCreate(['name' => $name]);
    }

    /**
     * Link Requirement to Group Set (Type: Compulsory)
     */
    private function linkRequirementToGroupSet(UniversityRequirement $requirement, UniversityRequirementGroupSet $groupSet): void
    {
        UniversityRequirementGroupSetItem::firstOrCreate([
            'university_requirement_id' => $requirement->id,
            'university_requirement_group_set_id' => $groupSet->id
        ]);
    }

    /**
     * Attach courses to a Group Set
     */
    private function attachCoursesToGroupSet(UniversityRequirementGroupSet $groupSet, array $courseCodes): void
    {
        $courseIds = [];
        foreach ($courseCodes as $code) {
             $course = $this->getCourse($code);
             if ($course) {
                 $courseIds[] = $course->id;
             } else {
                 $this->command->warn("Course not found for pool: $code");
             }
        }

        if (!empty($courseIds)) {
            $groupSet->courses()->syncWithoutDetaching($courseIds);
        }
    }

    /**
     * Get Course by code (Do not create)
     */
    private function getCourse(string $code): ?Course
    {
        return Course::where('code', $code)->first();
    }
}
