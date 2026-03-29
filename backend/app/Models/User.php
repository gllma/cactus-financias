<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';

    protected $fillable = [
        'name',
        'email',
        'password',
        'theme_preference',
    ];
}
