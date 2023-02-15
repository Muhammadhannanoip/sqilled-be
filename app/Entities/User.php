<?php

namespace App\Entities;

use App\Entities\Blog;
use Illuminate\Http\Request;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Nicolaslopezj\Searchable\SearchableTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, Billable, SearchableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $searchable = [
        'columns' => [
            'users.first_name'  => 10,
            'users.last_name'   => 10,
            'users.email'   => 10,
            'users.city'  => 10,
            'users.country'   => 10,
            'users.tag_line'    => 10,
            'users.qualification'    => 10,
            'users.certification'  => 10,
            'users.experience'   => 10,
            'users.state'   => 10,

        ]
    ];
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'avatar', 'provider', 'provider_id', 'city', 'country', 'tag_line', 'qualification', 'certification', 'experience', 'type', 'hourly_rate', 'min_hourly_rate', 'max_hourly_rate', 'device_token', 'time_zone', 'video_url', 'state'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * @return BelongsToMany
     */
    public function topic(): BelongsToMany
    {
        return $this->belongsToMany(TopicOfInterest::class);
    }
    public function blog(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    // /**
    //  * @return belongsTo
    //  */
    // public function topic(): HasMany
    // {
    //     return $this->hasMany(TopicOfInterest::class,'');
    // }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getTokens()
    {
        $request = Request::create('oauth/token', 'POST', [
            'grant_type' => 'personal_access',
            'client_id' => config('services.oauth.personal_access_client_id'),
            'client_secret' => config('services.oauth.personal_access_client_secret'),
            'user_id' => $this->id,
        ], [], [], [
            'HTTP_Accept' => 'application/json'
        ]);
        $response = app()->handle($request);
        $decodedResponse = json_decode($response->getContent(), true);
        return $decodedResponse;
    }

    // public function getAvatarAttribute($value)
    // {

    //     if ($value) {
    //                     $url = Storage::url($value);
    //                     return $url;
    //                 } 
    //     // if(empty($this->provider)) {
    //     //    if ($value) {
    //     //     $url = Storage::url($value);
    //     //     return $url;
    //     //     } 
    //     // }else {
    //     //     if (strpos($value, 'https') !== false) {
    //     //         return $value;
    //     //         }else {
    //     //             if ($value) {
    //     //                 $url = Storage::url($value);
    //     //                 return $url;
    //     //             } 
    //     //         }
    //     // }

    //     return $value;
    // }

    // public function getVideoUrlAttribute($value)
    // {

    //     if ($value) {
    //                     $url = Storage::url($value);
    //                     return $url;
    //                 } 

    //     return $value;
    // }
}
