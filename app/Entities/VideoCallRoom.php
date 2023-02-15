<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallRoom extends Model
{
    protected $fillable = [
        'user_id','author_id','booking_id','room_name','room_sid','saved_video_url'
    ];

}
