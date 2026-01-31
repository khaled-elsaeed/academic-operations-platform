<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityRequirement extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type', // 'elective', 'compulsory'
        'course_id',
    ];

    // If type is 'elective', this points to the single course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // If type is 'compulsory', it links to a Group Set (Pool)
    public function groupSet()
    {
        return $this->hasOneThrough(
            UniversityRequirementGroupSet::class,
            UniversityRequirementGroupSetItem::class,
            'university_requirement_id', // Foreign key on pivot table
            'id', // Foreign key on group set table
            'id', // Local key on requirements table
            'university_requirement_group_set_id' // Local key on pivot table
        );
    }
}
