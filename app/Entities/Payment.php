<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['user_id','charge_id','status','last4','booking_id','refunded',
    'brand','amount'];

    /**
     * @return belongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class,'booking_id','id');
    }
}
