<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Validation\ValidationException;

use App\Entities\User;
use App\Entities\Booking;
use App\Entities\VideoCallRoom;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Rest\Client;

use DB;


class EndVideoCall {

    public function __construct()
    {
        
    }
    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param  mixed[]  $args The arguments that were passed into the field.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context Arbitrary data that is shared between all fields of a single query.
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     * @return mixed
     */
    public function resolve($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) {
        try {

                $this->validator($args)->validate();
                $loggedin_user_id  = Auth::user()->id;
                // $walker = User::find($loggedin_user_id);
                $booking_data = Booking::findOrFail($args['booking_id']);
                if($booking_data->status != config('booking-status.completed')) {
                		// booking completed
                		
                        // $traveler = User::find($booking_data->traveller_id);
                        // $walker = User::find($booking_data->walker_id);
                        // $traveler = User::find($booking_data->traveller_id);
                        $room_details = VideoCallRoom::where('booking_id',$args['booking_id'])->first();
                        // $user = User::findOrFail($booking_data->traveller_id);

                        try{
                                    $token = getenv("TWILIO_AUTH_TOKEN");
                                    $accountSid = env('TWILIO_SID');
                                    $twilio = new Client($accountSid, $token);
                                    $room_status = $twilio->video->v1->rooms($room_details->room_sid)
                                      ->fetch();
                                      $booking_data->status = config('booking-status.completed');
                                      $booking_data->save();
                                      if($room_status->status != 'completed') {
                                            $room = $twilio->video->v1->rooms($room_details->room_sid)
                                                    ->update("completed");
                                             

                                                    // if($room->status == 'completed') {
                                                        
                                                        
                                                    // }
                                            
                                                return [
                                                    'status' => "SUCCESS",
                                                    'message' => "Video call ended successfully."
                                                ];
                                      }elseif($room_status->status == 'completed') {

                                        return [
                                                    'status' => "SUCCESS",
                                                    'message' => "Video call ended successfully."
                                                ];
                                      }else {
                                            return [
                                                'status' => "FAIL",
                                                'message' => "Unable to end video call"
                                            ];
                                      }
                                    
                                }catch (\Exception $ex) {
                                    // dd($ex);
                                    return [
                                        'status' => "FAIL",
                                        'message' => "Unable to end video call"
                                    ];
                                // do nothing...
                                }
                        
                        
                        
                          
                

                            
                                                    
                
                                
                }else {
                        return [
                            'status' => "FAIL",
                            'message' => "Video call ended already."
                        ];
                }
                
                
            
        } catch (ValidationException | ModelNotFoundException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
                "booking_id" => "required|exists:bookings,id",
            ]);
        
    }
}
