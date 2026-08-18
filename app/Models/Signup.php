<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signup extends Model
{
    protected $fillable = [
        'name',
        'email',
        'idnumber',
        'password',
        'role',
        'verification_token',
        'verified_at',
    ];

    protected $hidden = ['password'];
}
