<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudyPlan;
use App\Models\Course;
use App\Models\Program;
use App\Models\ElectiveCourse;

class StudyPlanSeeder extends Seeder
{
    public function run(): void
    {
        $computerEngineeringCourses = [
            // Semester 1
            ['semester_no'=>1, 'code'=>'CSE014','name'=>'Structured Programming','credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'MAT111','name'=>'Mathematics I','credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'MAT123','name'=>'Mechanics','credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'MEC011','name'=>'Engineering Drawing (1)','credit_hours'=>2],
            ['semester_no'=>1, 'code'=>'PHY212','name'=>'Introduction to Engineering Physics','credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'UC1','name'=>'University Requirement (1)','credit_hours'=>2],
            ['semester_no'=>1, 'code'=>'UE1','name'=>'Elective University (1)','credit_hours'=>2],

            // Semester 2
            ['semester_no'=>2, 'code'=>'CSE015','name'=>'Object Oriented Programming','credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'CSE113','name'=>'Electric & Electronic Circuits','credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'MAT112','name'=>'Mathematics II','credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'MAT131','name'=>'Statistics','credit_hours'=>2],
            ['semester_no'=>2, 'code'=>'PHY211','name'=>'Physics II','credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'UC2','name'=>'University Requirement (2)','credit_hours'=>2],

            // Semester 3
            ['semester_no'=>3, 'code'=>'AIE111','name'=>'Artificial Intelligence','credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'CSE111','name'=>'Data Structures','credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'CSE131','name'=>'Logic Design','credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'ELE212','name'=>'Electrical Measurements & Measuring Instruments','credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'MAT313','name'=>'Differential Equations & Numerical Analysis','credit_hours'=>4],
            ['semester_no'=>3, 'code'=>'UC3','name'=>'University Requirement (3)','credit_hours'=>2],

            // Semester 4
            ['semester_no'=>4, 'code'=>'AIE121','name'=>'Machine Learning','credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'CSE112','name'=>'Design & Analysis of Algorithms','credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'CSE132','name'=>'Computer Architecture & Organization','credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'CSE315','name'=>'Discrete Mathematics','credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'ELE432','name'=>'Digital Signal Processing','credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'UC4','name'=>'University Requirement (4)','credit_hours'=>2],

            // Semester 5
            ['semester_no'=>5, 'code'=>'CSE211','name'=>'Web Programming','credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'CSE233','name'=>'Operating Systems','credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'CSE241','name'=>'Security of Information Systems','credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'CSE261','name'=>'Computer Networks','credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'UE2','name'=>'Elective University Requirement (2)','credit_hours'=>2],

            // Semester 6
            ['semester_no'=>6, 'code'=>'CSE221','name'=>'Database Systems','credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE242','name'=>'Cryptography','credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE243','name'=>'Secure Programming','credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE251','name'=>'Software Engineering','credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE272','name'=>'Embedded Systems','credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE291','name'=>'Field Training 1 In Computer Engineering','credit_hours'=>2],

            // Semester 7
            ['semester_no'=>7, 'code'=>'CSE344','name'=>'Introduction to Cyber Security','credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'E1','name'=>'Elective Course (1)','credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'E3','name'=>'Elective Course (3)','credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'E4','name'=>'Elective Course (4)','credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'UC5','name'=>'University Requirement (5)','credit_hours'=>2],
            ['semester_no'=>7, 'code'=>'UC6','name'=>'University Requirement (6)','credit_hours'=>2],

            // Semester 8
            ['semester_no'=>8, 'code'=>'CSE322','name'=>'Big Data Analytics 1','credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'CSE363','name'=>'Cloud Computing','credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'CSE374','name'=>'Parallel Programming','credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'CSE392','name'=>'Field Training 2 In Computer Engineering','credit_hours'=>2],
            ['semester_no'=>8, 'code'=>'E2','name'=>'Elective Course (2)','credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'E5','name'=>'Elective Course (5)','credit_hours'=>3],

            // Semester 9
            ['semester_no'=>9, 'code'=>'CSE445','name'=>'Selected Topics in Information Security','credit_hours'=>3],
            ['semester_no'=>9, 'code'=>'CSE464','name'=>'Internet of Things','credit_hours'=>3],
            ['semester_no'=>9, 'code'=>'CSE493','name'=>'Graduation Project 1','credit_hours'=>2],
            ['semester_no'=>9, 'code'=>'E6','name'=>'Elective Course (6)','credit_hours'=>3],
            ['semester_no'=>9, 'code'=>'UC7','name'=>'University Requirement (7)','credit_hours'=>2],

            // Semester 10
            ['semester_no'=>10, 'code'=>'CSE446','name'=>'Information & Computer Networks Security','credit_hours'=>3],
            ['semester_no'=>10, 'code'=>'CSE447','name'=>'Selected Topics in Computer Security','credit_hours'=>3],
            ['semester_no'=>10, 'code'=>'CSE494','name'=>'Graduation Project 2','credit_hours'=>2],
            ['semester_no'=>10, 'code'=>'E7','name'=>'Elective Course (7)','credit_hours'=>3],
            ['semester_no'=>10, 'code'=>'UE3','name'=>'Elective University (3)','credit_hours'=>2],
        ];


    $aiEngineeringCourses = [
            // Semester 1
            ['semester_no'=>1, 'code'=>'CSE014', 'name'=>'Structured Programming', 'credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'MAT111', 'name'=>'Mathematics I', 'credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'MAT123', 'name'=>'Mechanics', 'credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'MEC011', 'name'=>'Engineering Drawing (1)', 'credit_hours'=>2],
            ['semester_no'=>1, 'code'=>'PHY212', 'name'=>'Introduction to Engineering Physics', 'credit_hours'=>3],
            ['semester_no'=>1, 'code'=>'UC1', 'name'=>'University Requirement (1)', 'credit_hours'=>2],
            ['semester_no'=>1, 'code'=>'UE1', 'name'=>'Elective University (1)', 'credit_hours'=>2],

            // Semester 2
            ['semester_no'=>2, 'code'=>'CSE015', 'name'=>'Object Oriented Programming', 'credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'CSE113', 'name'=>'Electric & Electronic Circuits', 'credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'MAT112', 'name'=>'Mathematics II', 'credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'MAT131', 'name'=>'Statistics', 'credit_hours'=>2],
            ['semester_no'=>2, 'code'=>'PHY211', 'name'=>'Physics II', 'credit_hours'=>3],
            ['semester_no'=>2, 'code'=>'UC2', 'name'=>'University Requirement (2)', 'credit_hours'=>2],

            // Semester 3
            ['semester_no'=>3, 'code'=>'AIE111', 'name'=>'Artificial Intelligence', 'credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'CSE111', 'name'=>'Data Structures', 'credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'CSE131', 'name'=>'Logic Design', 'credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'ELE212', 'name'=>'Electrical Measurements & Measuring Instruments', 'credit_hours'=>3],
            ['semester_no'=>3, 'code'=>'MAT313', 'name'=>'Differential Equations & Numerical Analysis', 'credit_hours'=>4],
            ['semester_no'=>3, 'code'=>'UC3', 'name'=>'University Requirement (3)', 'credit_hours'=>2],

            // Semester 4
            ['semester_no'=>4, 'code'=>'AIE121', 'name'=>'Machine Learning', 'credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'CSE112', 'name'=>'Design & Analysis of Algorithms', 'credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'CSE132', 'name'=>'Computer Architecture & Organization', 'credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'CSE315', 'name'=>'Discrete Mathematics', 'credit_hours'=>3],
            ['semester_no'=>4, 'code'=>'UC4', 'name'=>'University Requirement (4)', 'credit_hours'=>2],

            // Semester 5
            ['semester_no'=>5, 'code'=>'AIE231', 'name'=>'Neural Networks', 'credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'AIE241', 'name'=>'Natural Language Processing', 'credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'CSE233', 'name'=>'Operating Systems', 'credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'CSE261', 'name'=>'Computer Networks', 'credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'CSE281', 'name'=>'Image Processing', 'credit_hours'=>3],
            ['semester_no'=>5, 'code'=>'UE2', 'name'=>'Elective University Requirement (2)', 'credit_hours'=>2],

            // Semester 6
            ['semester_no'=>6, 'code'=>'AIE212', 'name'=>'Knowledge-Based Systems', 'credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'AIE213', 'name'=>'Optimization Techniques', 'credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'AIE291', 'name'=>'Field Training 1 In AI Engineering', 'credit_hours'=>2],
            ['semester_no'=>6, 'code'=>'CSE221', 'name'=>'Database Systems', 'credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE272', 'name'=>'Embedded Systems', 'credit_hours'=>3],
            ['semester_no'=>6, 'code'=>'CSE383', 'name'=>'Computer Vision', 'credit_hours'=>3],

            // Semester 7
            ['semester_no'=>7, 'code'=>'AIE322', 'name'=>'Advanced Machine Learning', 'credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'CSE251', 'name'=>'Software Engineering', 'credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'E1', 'name'=>'Elective Course (1)', 'credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'E3', 'name'=>'Elective Course (3)', 'credit_hours'=>3],
            ['semester_no'=>7, 'code'=>'UC5', 'name'=>'University Requirement (5)', 'credit_hours'=>2],
            ['semester_no'=>7, 'code'=>'UC6', 'name'=>'University Requirement (6)', 'credit_hours'=>2],

            // Semester 8
            ['semester_no'=>8, 'code'=>'AIE323', 'name'=>'Data Mining', 'credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'AIE332', 'name'=>'Deep Learning', 'credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'AIE351', 'name'=>'Robotics Design', 'credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'AIE392', 'name'=>'Field Training 2 in AI Engineering', 'credit_hours'=>2],
            ['semester_no'=>8, 'code'=>'E2', 'name'=>'Elective Course (2)', 'credit_hours'=>3],
            ['semester_no'=>8, 'code'=>'E4', 'name'=>'Elective Course (4)', 'credit_hours'=>3],

            // Semester 9
            ['semester_no'=>9, 'code'=>'AIE425', 'name'=>'Intelligent Recommender Systems', 'credit_hours'=>3],
            ['semester_no'=>9, 'code'=>'AIE493', 'name'=>'Graduation Project 1', 'credit_hours'=>2],
            ['semester_no'=>9, 'code'=>'E5', 'name'=>'Elective Course (5)', 'credit_hours'=>3],
            ['semester_no'=>9, 'code'=>'E6', 'name'=>'Elective Course (6)', 'credit_hours'=>3],
            ['semester_no'=>9, 'code'=>'UC7', 'name'=>'University Requirement (7)', 'credit_hours'=>2],

            // Semester 10
            ['semester_no'=>10, 'code'=>'AIE494', 'name'=>'Graduation Project 2', 'credit_hours'=>2],
            ['semester_no'=>10, 'code'=>'CSE344', 'name'=>'Introduction to Cyber Security', 'credit_hours'=>3],
            ['semester_no'=>10, 'code'=>'E7', 'name'=>'Elective Course (7)', 'credit_hours'=>3],
            ['semester_no'=>10, 'code'=>'E8', 'name'=>'Elective Course (8)', 'credit_hours'=>3],
            ['semester_no'=>10, 'code'=>'UE3', 'name'=>'Elective University (3)', 'credit_hours'=>2],
        ];


    $softwareEngineeringCourses = [
    // Semester 1
    ['semester_no'=>1, 'code'=>'CSE014', 'name'=>'Structured Programming', 'credit_hours'=>3],
    ['semester_no'=>1, 'code'=>'MAT114', 'name'=>'Analytical Geometry & Calculus I', 'credit_hours'=>4],
    ['semester_no'=>1, 'code'=>'PHY211', 'name'=>'Physics II', 'credit_hours'=>3],
    ['semester_no'=>1, 'code'=>'UC1', 'name'=>'University Requirement (1)', 'credit_hours'=>2],
    ['semester_no'=>1, 'code'=>'UC2', 'name'=>'University Requirement (2)', 'credit_hours'=>2],
    ['semester_no'=>1, 'code'=>'UE1', 'name'=>'Elective University (1)', 'credit_hours'=>2],

    // Semester 2
    ['semester_no'=>2, 'code'=>'CSE015', 'name'=>'Object Oriented Programming', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'CSE113', 'name'=>'Electric & Electronic Circuits', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'MAT112', 'name'=>'Mathematics II', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'MAT131', 'name'=>'Statistics', 'credit_hours'=>2],
    ['semester_no'=>2, 'code'=>'UC3', 'name'=>'University Requirement (3)', 'credit_hours'=>2],
    ['semester_no'=>2, 'code'=>'UE2', 'name'=>'Elective University Requirement (2)', 'credit_hours'=>2],

    // Semester 3
    ['semester_no'=>3, 'code'=>'CSE111', 'name'=>'Data Structures', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'CSE131', 'name'=>'Logic Design', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'CSE191', 'name'=>'Field Training 1 In Computer Science', 'credit_hours'=>2],
    ['semester_no'=>3, 'code'=>'MAT212', 'name'=>'Linear Algebra', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT231', 'name'=>'Probability & Statistics', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT313', 'name'=>'Differential Equations & Numerical Analysis', 'credit_hours'=>4],

    // Semester 4
    ['semester_no'=>4, 'code'=>'CSE112', 'name'=>'Design & Analysis of Algorithms', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE132', 'name'=>'Computer Architecture & Organization', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE221', 'name'=>'Database Systems', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE251', 'name'=>'Software Engineering', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE315', 'name'=>'Discrete Mathematics', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'UC4', 'name'=>'University Requirement (4)', 'credit_hours'=>2],

    // Semester 5
    ['semester_no'=>5, 'code'=>'AIE111', 'name'=>'Artificial Intelligence', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE211', 'name'=>'Web Programming', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE233', 'name'=>'Operating Systems', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE241', 'name'=>'Security of Information Systems', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE261', 'name'=>'Computer Networks', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'UC5', 'name'=>'University Requirement (5)', 'credit_hours'=>2],

    // Semester 6
    ['semester_no'=>6, 'code'=>'AIE121', 'name'=>'Machine Learning', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'CSE212', 'name'=>'Theory of Computation & Compilers', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'CSE292', 'name'=>'Field Training 2 In Computer Science', 'credit_hours'=>2],
    ['semester_no'=>6, 'code'=>'CSE323', 'name'=>'Advanced Database Systems', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'CSE352', 'name'=>'Systems Analysis & Design', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'UC6', 'name'=>'University Requirement (6)', 'credit_hours'=>2],
    ['semester_no'=>6, 'code'=>'UE3', 'name'=>'Elective University (3)', 'credit_hours'=>2],

    // Semester 7
    ['semester_no'=>7, 'code'=>'CSE313', 'name'=>'Mobile Development', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'CSE454', 'name'=>'Advanced Software Engineering', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'CSE475', 'name'=>'Distributed Information Systems', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'CSE493', 'name'=>'Graduation Project 1', 'credit_hours'=>2],
    ['semester_no'=>7, 'code'=>'E1', 'name'=>'Elective Course (1)', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'UC7', 'name'=>'University Requirement (7)', 'credit_hours'=>2],

    // Semester 8
    ['semester_no'=>8, 'code'=>'AIE323', 'name'=>'Data Mining', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'CSE312', 'name'=>'Advanced Web Programming', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'CSE363', 'name'=>'Cloud Computing', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'CSE494', 'name'=>'Graduation Project 2', 'credit_hours'=>2],
    ['semester_no'=>8, 'code'=>'E2', 'name'=>'Elective Course (2)', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'E3', 'name'=>'Elective Course (3)', 'credit_hours'=>3],
];

$aisCourses = [
    // Semester 1
    ['semester_no'=>1, 'code'=>'CSE014', 'name'=>'Structured Programming', 'credit_hours'=>3],
    ['semester_no'=>1, 'code'=>'MAT114', 'name'=>'Analytical Geometry & Calculus I', 'credit_hours'=>4],
    ['semester_no'=>1, 'code'=>'PHY211', 'name'=>'Physics II', 'credit_hours'=>3],
    ['semester_no'=>1, 'code'=>'UC1', 'name'=>'University Requirement (1)', 'credit_hours'=>2],
    ['semester_no'=>1, 'code'=>'UE1', 'name'=>'Elective University (1)', 'credit_hours'=>2],
    ['semester_no'=>1, 'code'=>'UE2', 'name'=>'Elective University Requirement (2)', 'credit_hours'=>2],

    // Semester 2
    ['semester_no'=>2, 'code'=>'CSE015', 'name'=>'Object Oriented Programming', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'CSE113', 'name'=>'Electric & Electronic Circuits', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'MAT112', 'name'=>'Mathematics II', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'MAT131', 'name'=>'Statistics', 'credit_hours'=>2],
    ['semester_no'=>2, 'code'=>'UC2', 'name'=>'University Requirement (2)', 'credit_hours'=>2],
    ['semester_no'=>2, 'code'=>'UC3', 'name'=>'University Requirement (3)', 'credit_hours'=>2],

    // Semester 3
    ['semester_no'=>3, 'code'=>'AIE111', 'name'=>'Artificial Intelligence', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'CSE111', 'name'=>'Data Structures', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'CSE131', 'name'=>'Logic Design', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT212', 'name'=>'Linear Algebra', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT231', 'name'=>'Probability & Statistics', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT313', 'name'=>'Differential Equations & Numerical Analysis', 'credit_hours'=>4],

    // Semester 4
    ['semester_no'=>4, 'code'=>'AIE121', 'name'=>'Machine Learning', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE112', 'name'=>'Design & Analysis of Algorithms', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE132', 'name'=>'Computer Architecture & Organization', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE221', 'name'=>'Database Systems', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE251', 'name'=>'Software Engineering', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE315', 'name'=>'Discrete Mathematics', 'credit_hours'=>3],

    // Semester 5
    ['semester_no'=>5, 'code'=>'AIE191', 'name'=>'Field Training 1 In AI Science', 'credit_hours'=>2],
    ['semester_no'=>5, 'code'=>'AIE241', 'name'=>'Natural Language Processing', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'AIE323', 'name'=>'Data Mining', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE233', 'name'=>'Operating Systems', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE261', 'name'=>'Computer Networks', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE281', 'name'=>'Image Processing', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'UE3', 'name'=>'Elective University (3)', 'credit_hours'=>2],

    // Semester 6
    ['semester_no'=>6, 'code'=>'AIE212', 'name'=>'Knowledge-Based Systems', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'AIE213', 'name'=>'Optimization Techniques', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'AIE231', 'name'=>'Neural Networks', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'AIE292', 'name'=>'Field Training 2 In AI Science', 'credit_hours'=>2],
    ['semester_no'=>6, 'code'=>'E1', 'name'=>'Elective Course (1)', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'UC4', 'name'=>'University Requirement (4)', 'credit_hours'=>2],
    ['semester_no'=>6, 'code'=>'UC5', 'name'=>'University Requirement (5)', 'credit_hours'=>2],

    // Semester 7
    ['semester_no'=>7, 'code'=>'AIE322', 'name'=>'Advanced Machine Learning', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'AIE332', 'name'=>'Deep Learning', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'AIE425', 'name'=>'Intelligent Recommender Systems', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'AIE493', 'name'=>'Graduation Project 1', 'credit_hours'=>2],
    ['semester_no'=>7, 'code'=>'E2', 'name'=>'Elective Course (2)', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'UC6', 'name'=>'University Requirement (6)', 'credit_hours'=>2],

    // Semester 8
    ['semester_no'=>8, 'code'=>'AIE351', 'name'=>'Robotics Design', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'AIE494', 'name'=>'Graduation Project 2', 'credit_hours'=>2],
    ['semester_no'=>8, 'code'=>'CSE344', 'name'=>'Introduction to Cyber Security', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'E3', 'name'=>'Elective Course (3)', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'UC7', 'name'=>'University Requirement (7)', 'credit_hours'=>2],
];

$bdiCourses = [
    // Semester 1
    ['semester_no'=>1, 'code'=>'CSE014', 'name'=>'Structured Programming', 'credit_hours'=>3],
    ['semester_no'=>1, 'code'=>'MAT114', 'name'=>'Analytical Geometry & Calculus I', 'credit_hours'=>4],
    ['semester_no'=>1, 'code'=>'PHY261', 'name'=>'Biophysics', 'credit_hours'=>3],
    ['semester_no'=>1, 'code'=>'UC1', 'name'=>'University Requirement (1)', 'credit_hours'=>2],
    ['semester_no'=>1, 'code'=>'UC2', 'name'=>'University Requirement (2)', 'credit_hours'=>2],
    ['semester_no'=>1, 'code'=>'UE1', 'name'=>'Elective University (1)', 'credit_hours'=>2],

    // Semester 2
    ['semester_no'=>2, 'code'=>'BIO241', 'name'=>'Biology II', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'CSE015', 'name'=>'Object Oriented Programming', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'MAT112', 'name'=>'Mathematics II', 'credit_hours'=>3],
    ['semester_no'=>2, 'code'=>'MAT131', 'name'=>'Statistics', 'credit_hours'=>2],
    ['semester_no'=>2, 'code'=>'UC3', 'name'=>'University Requirement (3)', 'credit_hours'=>2],
    ['semester_no'=>2, 'code'=>'UE2', 'name'=>'Elective University Requirement (2)', 'credit_hours'=>2],

    // Semester 3
    ['semester_no'=>3, 'code'=>'BIO222', 'name'=>'Molecular Genetics', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'BMD191', 'name'=>'Field Training 1 In Biomedical Informatics', 'credit_hours'=>2],
    ['semester_no'=>3, 'code'=>'CSE111', 'name'=>'Data Structures', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'CSE131', 'name'=>'Logic Design', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT212', 'name'=>'Linear Algebra', 'credit_hours'=>3],
    ['semester_no'=>3, 'code'=>'MAT313', 'name'=>'Differential Equations & Numerical Analysis', 'credit_hours'=>4],

    // Semester 4
    ['semester_no'=>4, 'code'=>'BMD241', 'name'=>'Human Physiology', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE112', 'name'=>'Design & Analysis of Algorithms', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE221', 'name'=>'Database Systems', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE251', 'name'=>'Software Engineering', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'CSE315', 'name'=>'Discrete Mathematics', 'credit_hours'=>3],
    ['semester_no'=>4, 'code'=>'UC4', 'name'=>'University Requirement (4)', 'credit_hours'=>2],

    // Semester 5
    ['semester_no'=>5, 'code'=>'AIE111', 'name'=>'Artificial Intelligence', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'BMD311', 'name'=>'Introduction to Bioinformatics', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'BMD351', 'name'=>'Biomedical Data Acquisition', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE261', 'name'=>'Computer Networks', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'CSE281', 'name'=>'Image Processing', 'credit_hours'=>3],
    ['semester_no'=>5, 'code'=>'UC5', 'name'=>'University Requirement (5)', 'credit_hours'=>2],

    // Semester 6
    ['semester_no'=>6, 'code'=>'AIE121', 'name'=>'Machine Learning', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'BMD292', 'name'=>'Field Training 2 In Biomedical Informatics', 'credit_hours'=>2],
    ['semester_no'=>6, 'code'=>'BMD361', 'name'=>'Biomedical Statistics', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'CSE352', 'name'=>'Systems Analysis & Design', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'E1', 'name'=>'Elective Course (1)', 'credit_hours'=>3],
    ['semester_no'=>6, 'code'=>'UC6', 'name'=>'University Requirement (6)', 'credit_hours'=>2],
    ['semester_no'=>6, 'code'=>'UE3', 'name'=>'Elective University (3)', 'credit_hours'=>2],

    // Semester 7
    ['semester_no'=>7, 'code'=>'BIO411', 'name'=>'Genome Regulation', 'credit_hours'=>4],
    ['semester_no'=>7, 'code'=>'BMD421', 'name'=>'Biomedical Information Systems', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'BMD493', 'name'=>'Graduation Project 1', 'credit_hours'=>2],
    ['semester_no'=>7, 'code'=>'CSE383', 'name'=>'Computer Vision', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'E2', 'name'=>'Elective Course (2)', 'credit_hours'=>3],
    ['semester_no'=>7, 'code'=>'UC7', 'name'=>'University Requirement (7)', 'credit_hours'=>2],

    // Semester 8
    ['semester_no'=>8, 'code'=>'BMD413', 'name'=>'Structural Bioinformatics', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'BMD494', 'name'=>'Graduation Project 2', 'credit_hours'=>2],
    ['semester_no'=>8, 'code'=>'CSE313', 'name'=>'Mobile Development', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'E3', 'name'=>'Elective Course (3)', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'E4', 'name'=>'Elective Course (4)', 'credit_hours'=>3],
    ['semester_no'=>8, 'code'=>'E5', 'name'=>'Elective Course (5)', 'credit_hours'=>3],
];


        $programCourses = [
            1 => $bdiCourses,
            2 => $softwareEngineeringCourses,
            3 => $aisCourses,
            4 => $computerEngineeringCourses,
            5 => $aiEngineeringCourses,
        ];

        foreach ($programCourses as $programId => $courses) {
            foreach ($courses as $item) {
                $type = str_starts_with($item['code'], 'E') ? 'elective' : 'normal';

                // Handle elective courses
                $electiveCourseId = null;
                if ($type === 'elective') {
                    $electiveCourse = ElectiveCourse::where('code', $item['code'])->first();
                    if ($electiveCourse) {
                        $electiveCourseId = $electiveCourse->id;
                    }
                }

                // Add to study plan with type and elective_course_id
                $course = Course::where('code', $item['code'])->first();

                if ($course || $electiveCourseId) {
                    StudyPlan::create([
                        'program_id' => $programId,
                        'semester_no' => $item['semester_no'],
                        'course_id' => $course?->id,
                        'elective_course_id' => $electiveCourseId,
                        'type' => $type,
                    ]);
                }
            }
        }
    }
}
