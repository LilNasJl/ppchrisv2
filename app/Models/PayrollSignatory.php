<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSignatory extends Model
{
    protected $fillable = [
        'context',
        'prepared_by',
        'checked_by',
        'approved_by',
    ];

    public static function default(): self
    {
        return static::query()->firstOrCreate(
            ['context' => 'default'],
            [
                'prepared_by' => 'Prepared By',
                'checked_by' => 'Checked By',
                'approved_by' => 'Approved By',
            ],
        );
    }
}
