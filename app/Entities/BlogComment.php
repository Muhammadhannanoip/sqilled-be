<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogComment extends Model
{
    protected $fillable = [
        'author_id','blog_id','comment'
    ];
 
    public function author(): HasOne
    {
        return $this->HasOne(User::class,'id','author_id');
    }

}
