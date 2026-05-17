<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
    ];

    public function memos()
    {
        return $this->hasMany(Memo::class);
    }
}
