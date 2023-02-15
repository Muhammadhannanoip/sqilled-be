<?php

namespace App\Entities;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    protected $fillable = [
        'user_id','title','thumbnail','thumbnail_alt','description','text','video_url','created_at'
    ];
    public function blog(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }
    public function user(): HasOne
    {
        return $this->HasOne(User::class,'id','user_id');
    }
    public function comments(): HasMany
    {
        return $this->HasMany(BlogComment::class,'blog_id','id');
    }
    public function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->timezone('UTC')->toDateTimeString();
    }
}
