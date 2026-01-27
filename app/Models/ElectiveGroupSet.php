<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectiveGroupSet extends Model
{
    protected $fillable = [
        'name',
    ];

    public function electives()
    {
        return $this->hasMany(ElectiveGroupSetItem::class);
    }

}
