<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'date_from',
        'date_to',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];
}
