<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectiveGroupSetItem extends Model
{
    protected $fillable = [
        'elective_group_set_id',
        'elective_group_id',
    ];

    public function elective()
    {
        return $this->belongsTo(ElectiveCourse::class, 'elective_group_id');
    }

}
