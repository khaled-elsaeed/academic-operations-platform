<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumElectiveCourse extends Model
{
    protected $fillable = [
        'curriculum_elective_group_id',
        'course_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function curriculumElectiveGroup()
    {
        return $this->belongsTo(CurriculumElectiveGroup::class);
    }

}
