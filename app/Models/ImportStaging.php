<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportStaging extends Model
{
    protected $table = 'import_staging';

    protected $fillable = [
        'task_id',
        'import_type',
        'row_data',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];
}