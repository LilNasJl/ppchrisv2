<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanType extends Model
{
    protected $fillable = ['name'];

    public static function options(): array
    {
        return self::query()->orderBy('name')->pluck('name', 'name')->all();
    }
}
