<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Support\Facades\Storage;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Read the CSV file
        $csvPath = storage_path('app/private/seeders/nmu_courses.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", trim($csvContent));

        if (count($lines) <= 1) {
            $this->command->warn("CSV file appears to be empty or only contains a header.");
            return;
        }

        // Assume the first line is the header, so skip it
        $headerSkipped = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }
            if (empty($line)) continue;
            
            $data = str_getcsv($line);
            
            if (count($data) < 5) {
                $this->command->warn("Skipping invalid line: {$line}");
                continue;
            }
            
            $code = trim($data[0]);
            $title = trim($data[1]);
            $creditHours = (int) trim($data[2]);
            $prerequisites = trim($data[3]);
            $facultyName = trim($data[5]);
            
            // Find the faculty
            $faculty = Faculty::where('name', $facultyName)->first();
            
            if (!$faculty) {
                // Create the faculty if not found
                $faculty = Faculty::create(['name' => $facultyName]);
                $this->command->info("Faculty created: {$facultyName} for course {$code}");
            }
            
            // Create or update the course
            $course = Course::firstOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    'credit_hours' => $creditHours,
                    'faculty_id' => $faculty->id,
                ]
            );
            
            // Handle prerequisites
            if ($prerequisites && $prerequisites !== '- - -' && $prerequisites !== 'SENIOR STANDING') {
                $this->createPrerequisites($course, $prerequisites);
            }
        }
        
        $this->command->info('Courses seeded successfully from CSV file.');
    }
    
    private function createPrerequisites(Course $course, string $prerequisitesString): void
    {
        // Split prerequisites by comma and clean up
        $prerequisiteCodes = array_map('trim', explode(',', $prerequisitesString));
        
        foreach ($prerequisiteCodes as $index => $prerequisiteCode) {
            if (empty($prerequisiteCode) || $prerequisiteCode === '- - -') {
                continue;
            }
            
            // Find the prerequisite course
            $prerequisiteCourse = Course::where('code', $prerequisiteCode)->first();
            
            if ($prerequisiteCourse && $prerequisiteCourse->id !== $course->id) {
                // Attach prerequisite with order
                $course->prerequisites()->syncWithoutDetaching([
                    $prerequisiteCourse->id => ['order' => $index + 1]
                ]);
            }
        }
    }
} 