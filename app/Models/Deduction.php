<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deduction extends Model
{
    use SoftDeletes;

    public const DEFAULT_TITLES = [
        'SHORTAGES',
        'COMPANY UNIFORM',
        'SSS LOAN',
        'SSS EE',
        'HDMF LOAN',
        'HDMF EE',
        'PHIC EE',
    ];

    protected $fillable = [
        'title',
        'description',
        'amount'
    ];

    public static function defaultTitles(): array
    {
        return self::DEFAULT_TITLES;
    }
    
}
