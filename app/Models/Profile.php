<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'avatar',
        'address',
        'phone_number',
        'created_by',
        'updated_by',
        'user_id', // 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

