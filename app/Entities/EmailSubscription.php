<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\carbon;

class EmailSubscription extends Model
{
    protected $fillable = [
        'user_id','charge_amount','email','author_id'
    ];

    

    /**
     * @return belongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    /**
     * @return belongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class,'author_id','id');
    }

}
