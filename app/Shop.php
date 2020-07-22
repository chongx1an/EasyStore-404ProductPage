<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{

    protected $fillable = [
        'url',
        'access_token',
    ];

    protected $hidden = [
        'is_deleted',
        'updated_at',
        'created_at'
    ];

}
