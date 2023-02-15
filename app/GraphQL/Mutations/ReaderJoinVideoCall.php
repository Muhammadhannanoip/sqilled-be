<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use App\Entities\User;
use App\Entities\Booking;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Rest\Client;
use App\Entities\VideoCallRoom;
use App\Traits\SendPushNotificationTrait;


class ReaderJoinVideoCall {
    
    use SendPushNotificationTrait;

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
                // $data = $args['data'];
                
                $this->validator($args)->validate();
                $loggedin_user_id  = Auth::user()->id;
                $booking = Booking::findOrFail($args['booking_id']);
                $reader = User::findOrFail($booking->user_id);
                $author = User::findOrFail($booking->author_id);
                // $user = User::findOrFail($loggedin_user_id);
                $booking->is_user_join = 1;
                $booking->save();
                
                





                // twilio credentials
                    $accountSid = env('TWILIO_SID');
                    $apiKeySid = env('TWILIO_API_KEY_SID');
                    $apiKeySecret = env('TWILIO_API_KEY_SECRET');
                    $room_name = $author->first_name.substr(md5(microtime()),rand(0,26),5);

                    $isRoomExist = VideoCallRoom::where('booking_id',$args['booking_id'])->first();
                        if(!empty($isRoomExist)) {
                            $room_unique_name = $isRoomExist->room_name;
                            $room_sid = $isRoomExist->room_sid;
                            $data['author_id'] = $author->id;
                            $data['user_id'] = $reader->id;
                            $data['room_name'] = $room_unique_name;
                            $data['room_sid'] = $room_sid;
                        }else {
                            // create room
                            $token = getenv("TWILIO_AUTH_TOKEN");
                            $twilio = new Client($accountSid, $token);
                            // 
                            $room = $twilio->video->v1->rooms
                                      ->create([
                                                   // "recordParticipantsOnConnect" => True,
                                                   "type" => "group-small",
                                                   "uniqueName" => $room_name,
                                                   "maxParticipants" => 2
                                               ]
                                      );


                            
                            $room_unique_name = $room->uniqueName;
                            $room_sid = $room->sid;
                            $data['author_id'] = $author->id;
                            $data['user_id'] = $reader->id;
                            $data['room_name'] = $room->uniqueName;
                            $data['room_sid'] = $room->sid;
                            $data['booking_id'] = $args['booking_id'];
                            $room_data = $this->InsertRoomData($data);
                        }

                            // create jwt token 
                            $identity = uniqid();

                            // Create an Access Token
                            $token = new AccessToken(
                                $accountSid,
                                $apiKeySid,
                                $apiKeySecret,
                                3600,
                                $identity
                            );

                            // Grant access to Video
                            $grant = new VideoGrant();
                            $grant->setRoom($room_unique_name);
                            $token->addGrant($grant);

                    // Serialize the token as a JWT
                       $jwtToken =  $token->toJWT();
                        if(!empty($author->device_token)){
                            $title = "Hello Join video call";
                            $body = "Hello Join video call";
                                                
                            $payloadData['reader_id'] = $loggedin_user_id;
                            $payloadData['room_name'] = $data['room_name'];
                            $payloadData['room_sid'] = $data['room_sid'];
                            
                            $payloadData['user_type'] = 'author';
                            $result = $this->sendPushNotification($title,$body, $payloadData,$author->device_token);
                        }

                        
                
                            if($jwtToken) {
                                return [
                                            'status' => 'SUCCESS',
                                            'jwt_token' => $jwtToken,
                                            'room_name' => $room_unique_name,
                                            'room_sid' => $room_sid
                                        ];
                            }else {
                                return [
                                            'status' => 'FAIL',
                                            'jwt_token' => null,
                                            'room_name' => null,
                                            'room_sid' => null

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

        "booking_id" => "required|exists:bookings,id"

         ]);
        
    }

    //Insert room data
    public function InsertRoomData(array $data) {
            return VideoCallRoom::create($data);
    }
}
