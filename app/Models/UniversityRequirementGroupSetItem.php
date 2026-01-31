<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityRequirementGroupSetItem extends Model
{
    protected $fillable = [
        'university_requirement_id',
        'university_requirement_group_set_id',
    ];

    public function requirement()
    {
        return $this->belongsTo(UniversityRequirement::class, 'university_requirement_id');
    }

    public function groupSet()
    {
        return $this->belongsTo(UniversityRequirementGroupSet::class, 'university_requirement_group_set_id');
    }
}
