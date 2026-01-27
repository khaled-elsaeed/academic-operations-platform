<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumElectiveGroup extends Model
{
    protected $fillable = [
        'program_id',
        'elective_group_set_id',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function groupSet()
    {
        return $this->belongsTo(ElectiveGroupSet::class, 'elective_group_set_id');
    }

    public function courses()
    {
        return $this->hasMany(CurriculumElectiveCourse::class);
    }

}
