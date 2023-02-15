<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use App\Entities\User;
use App\Entities\Booking;
use App\Entities\AuthorUnavailableDate;
use DB;
use Auth;

class CheckBookingSlotAvailability
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
        // dd($args['date']);
        $check_date = $args['date']->format('Y-m-d');
        // dd($check_date);
        $start_time = [
            "01:00:00",
            "02:00:00",
            "03:00:00",
            "04:00:00",
            "06:00:00",
            "07:00:00",
            "08:00:00",
            "09:00:00",
            "10:00:00",
            "11:00:00",
            "12:00:00",
            "13:00:00",
            "14:00:00",
            "15:00:00",
            "16:00:00",
            "17:00:00",
            "18:00:00",
            "19:00:00",
            "20:00:00",
            "21:00:00",
            "22:00:00",
            "23:00:00",
            "24:00:00",
        ];

        

        $data = AuthorUnavailableDate::where('unavailable_date',$check_date)->get()->toArray();
        // dd($data);
        foreach ($data as $key => $value) {
                if (in_array($value['start_time'], $start_time)) {
                    if (($key = array_search($value['start_time'], $start_time)) !== false) {
                        // dd($key);
                        unset($start_time[$key]);
                        // unset($end_time[$key]);
                    }
                    
                }


        }
        $data = Booking::where('booking_date',$check_date)->where('status',0)->get()->toArray();

        foreach ($data as $key => $value) {
                if (in_array($value['start_time'], $start_time)) {
                    if (($key = array_search($value['start_time'], $start_time)) !== false) {
                        // dd($key);
                        unset($start_time[$key]);
                        // unset($end_time[$key]);
                    }
                    
                }

               
        }
         // dd($start_time);
         return ['start_time' => $start_time];
         
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        return Validator::make($data, [
                "date" => "required"
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
