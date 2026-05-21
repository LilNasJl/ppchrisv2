<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'profile_photo_path', 'is_disabled'])]
#[Hidden(['password', 'remember_token'])]
class SystemAccount extends User
{
    protected $table = 'users';

    public function getMorphClass(): string
    {
        return User::class;
    }
}
