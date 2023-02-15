<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class UserTopicOfInterest extends Model
{
     protected $fillable = [
        'id','user_id','topic_id'
    ];

    
}
