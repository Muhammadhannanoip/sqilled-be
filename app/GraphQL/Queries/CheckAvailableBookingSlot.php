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

class checkAvailableBookingSlot
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
        $data = $args['data'];
        $loggedin_user_id  = Auth::user()->id;
        $this->validator($data)->validate();

        $unavailable_slot = AuthorUnavailableDate::select('start_time','end_time')->where('unavailable_date',$data['date']) ->where('author_id',$data['author_id'])->get()->toArray();

        $booking_slot = Booking::select('start_time','end_time')->where('booking_date',$data['date'])->where('author_id',$data['author_id'])->where('status',0)->get()->toArray();

        $final_array = array_merge($unavailable_slot,$booking_slot);
  
        // $is_in_unavailable_slot = AuthorUnavailableDate::whereBetween('start_time', [$data['start_time'], $data['end_time']])
        //                                 ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
        //                                 // ->where('time_start', '<', $request->time_start)
        //                                 // ->where('time_end', '>', $request->time_end)
        //                                 // ->where('unavailable_date',$data['date'])
        //                                 ->where('author_id',$data['author_id'])
        //                                 ->exists();
        $unavailable_slots_data = AuthorUnavailableDate::where('unavailable_date',$data['date'])
                                        ->where('author_id',$data['author_id'])
                                        ->get()->toArray();
        $unavailable_slots_array = [];
        foreach ($unavailable_slots_data as $key => $value) {
            $data_set = $this->SplitTime($value['start_time'], $value['end_time'], "60");
            $unavailable_slots_array = array_merge($unavailable_slots_array,$data_set);

        }
            $booking_slots = [
                $data['start_time'],
                $data['end_time']
            ];

            $is_in_unavailable_slot = count(array_intersect($booking_slots, $unavailable_slots_array)) === count($booking_slots);

            $is_in_booking_slot = Booking::whereBetween('start_time', [$data['start_time'], $data['end_time']])
                                        ->where('status', 0)
                                        ->where('booking_date',$data['date'])
                                        ->where('author_id',$data['author_id'])
                                        ->exists();
        // print_r($is_in_unavailable_slot);
        // echo "-----------";
        // // echo $is_in_booking_slot;
        // echo "---------------";
        // dd($is_in_booking_slot);


        if($is_in_unavailable_slot || $is_in_booking_slot ) {
            return [
                'status' => "UNAVAILABLE",
                'message' => "The selected slot is unavailable",
                'unavailable_time_slot' => $final_array

            ];
        }else {
            return [
                'status' => "AVAILABLE",
                'message' => "The selected slot is available",
                'unavailable_time_slot' => []

            ];
        }
         
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        return Validator::make($data, [
                "date" => "required",
                "start_time" => "required",
                "end_time" => "required",
                "author_id"  => "required|exists:users,id"
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

    public function SplitTime($StartTime, $EndTime, $Duration="60"){
            $ReturnArray = array ();// Define output
            $StartTime    = strtotime ($StartTime); //Get Timestamp
            $EndTime      = strtotime ($EndTime); //Get Timestamp

            $AddMins  = $Duration * 60;

            while ($StartTime <= $EndTime) //Run loop
            {
                $ReturnArray[] = date ("G:i:s", $StartTime);
                $StartTime += $AddMins; //Endtime check
            }
            return $ReturnArray;
    }
}
