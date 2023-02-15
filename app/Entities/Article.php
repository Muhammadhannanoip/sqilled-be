<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
     protected $fillable = [
        'user_id','title','content','published_date','status'
    ];
}
