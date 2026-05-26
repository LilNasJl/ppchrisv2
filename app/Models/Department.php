<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'acronym',
    ];
}
