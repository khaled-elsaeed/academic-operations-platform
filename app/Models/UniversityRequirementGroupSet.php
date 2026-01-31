<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityRequirementGroupSet extends Model
{
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(UniversityRequirementGroupSetItem::class);
    }

    public function requirements()
    {
        return $this->hasManyThrough(
            UniversityRequirement::class,
            UniversityRequirementGroupSetItem::class,
            'university_requirement_group_set_id',
            'id',
            'id',
            'university_requirement_id'
        );
    }

    // The pool of courses available for this group set
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'university_requirement_courses', 'university_requirement_group_set_id', 'course_id');
    }
}
