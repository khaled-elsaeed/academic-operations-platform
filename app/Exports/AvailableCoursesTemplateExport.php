<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AvailableCoursesTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Example row
        return [
            [
                'CSE015',       
                'Object Oriented Programming',  
                '2243',         
                'Lecture',      
                '1',            
                'Saturday',     
                '1',            
                '09:00 - 09:50',
                'Dr.Mohamed Handousa', 
                'Room(2-1-16)', 
                'N',            
                '1',            
                'AIE',          
                'SCH-22433'
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Course Code',
            'Course Name',
            'Term',
            'Activity Type',
            'Grouping',
            'Day',
            'Slot',
            'Time',
            'Instructor',
            'Location',
            'External (Y/N)',
            'Level',
            'Program',
            'Schedule Code'
        ];
    }
} 