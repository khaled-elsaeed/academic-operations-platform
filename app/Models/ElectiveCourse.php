<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectiveCourse extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function groupSets()
    {
        return $this->belongsToMany(ElectiveGroupSet::class, 'elective_group_set_items', 'elective_group_id', 'elective_group_set_id');
    }

}
