<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\carbon;

class Booking extends Model
{
    protected $fillable = [
        'user_id','author_id','booking_date','start_time','end_time','status','booking_type'
    ];

    /**
     * @return belongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class,'author_id','id');
    }

    /**
     * @return belongsTo
     */
    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

}
