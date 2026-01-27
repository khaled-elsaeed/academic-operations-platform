<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyPlan extends Model
{
    use HasFactory;

    protected $table = 'study_plan';
    protected $fillable = ['program_id', 'semester_no', 'course_id', 'elective_course_id'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function electiveCourse()
    {
        return $this->belongsTo(ElectiveCourse::class);
    }
}
