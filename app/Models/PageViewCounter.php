<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageViewCounter extends Model
{
    protected $fillable = [
        'route_name',
        'views',
    ];
}
