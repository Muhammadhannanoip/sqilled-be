<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\carbon;

class AuthorUnavailableDate extends Model
{
    protected $fillable = [
        'author_id','unavailable_date','start_time','end_time'
    ];

 //    protected $casts = [
 //    	'start_time' => 'datetime:H:i:s',
 //    	'end_time' => 'datetime:H:i:s',
	// ];

}
