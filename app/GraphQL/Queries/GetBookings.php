<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use App\Entities\User;
use App\Entities\Booking;
use DB;
use Auth;

class GetBookings
{
    
    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param  mixed[]  $args The arguments that were passed into the field.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context Arbitrary data that is shared between all fields of a single query.
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     * @return mixed
     */
    public function resolve($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $loggedin_user_id  = Auth::user()->id;
        $this->validator($args)->validate();
         $user = User::findOrFail($loggedin_user_id);
         if($user->type == "W") {
            return Booking::where('author_id',$loggedin_user_id)->where('status',$args['status'])->get();
         }else {
            return Booking::where('user_id',$loggedin_user_id)->where('status',$args['status'])->get();
         }
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        return Validator::make($data, [
                "status" => "required"
            ]);
    }

    public function getUsersBooking ($root, array $args,GraphQLContext $context) {
        $loggedin_user_id  = Auth::user()->id;
        $this->validator($args)->validate();
         $user = User::findOrFail($loggedin_user_id);
         if($user->type == "W") {
            return Booking::where('author_id',$loggedin_user_id)->where('status',$args['status'])->orderBy('id',"DESC");
         }else {
            return Booking::where('user_id',$loggedin_user_id)->where('status',$args['status'])->orderBy('id',"DESC");
         }
    }

    public function getExpiredAndCancelledBooking ($root, array $args,GraphQLContext $context) {
            $loggedin_user_id  = Auth::user()->id;
             $user = User::findOrFail($loggedin_user_id);
                 if($user->type == "W") {
                    return Booking::where('author_id',$loggedin_user_id)->where('status',config('booking-status.cancelled'))->orWhere('status',config('booking-status.expired'))->orderBy('id',"DESC");
                 }else {
                    return Booking::where('author_id',$loggedin_user_id)->where('status',config('booking-status.cancelled'))->orWhere('status',config('booking-status.expired'))->orderBy('id',"DESC");
                 }
    }
}
