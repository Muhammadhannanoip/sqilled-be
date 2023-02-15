<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use App\Entities\User;
use App\Entities\Booking;
use Illuminate\Support\Facades\Validator;
use App\Entities\Payment;
use Auth;
use Illuminate\Support\Carbon;
use DateTime;
use DateTimeZone;


class CancelledBooking {

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
                
                $loggedin_user_id  = Auth::user()->id;
                $this->validator($args)->validate();
                $booking =  Booking::findOrFail($args['booking_id']);
                $user = User::find($loggedin_user_id);

                if($booking->status != config('booking-status.cancelled') && ($booking->booking_type == "PAID" )) {
                    if($booking->author_id == $loggedin_user_id ) {
                        $payments = Payment::where('booking_id',$args['booking_id'])->where('refunded',0)->first();
                        $stripe = new \Stripe\StripeClient(getenv("STRIPE_SECRET"));
                        $result = $stripe->refunds->create([
                                'charge' => $payments->charge_id,
                            ]);
                        $response['booking_id'] = $args['booking_id'];
                        $response['user_id'] = $payments->user_id;
                        $response['amount'] = $result->amount * 0.01;
                        $response['charge_id'] = $result->charge;
                        $response['refunded'] = 1;
                         $response['last4'] = $payments->last4;
                            $response['brand'] = $payments->brand;
                            $response['status'] = 'succeeded';
                        $payment = Payment::create($response);
                    }else {
                        // transfer 80 percent
                         if(!empty($user->time_zone)) {
                                $time_zone = $user->time_zone;
                            }else {
                                $time_zone = 'UTC';
                            }
                        $datetime =  new DateTime( "now", new DateTimeZone( $time_zone ) ); 
                        $current_date = $datetime->format('Y-m-d H:i:s');
                        $booking_date = new DateTime($booking->booking_date.' '.$booking->start_time , new DateTimeZone( $time_zone ) );
                        $booking_date = $booking_date->format('Y-m-d H:i:s');
                        $diff = strtotime($booking_date) - strtotime($current_date);
                        $diff_in_hrs = $diff/3600;
                        if($diff_in_hrs >= 8) {
                            $payments = Payment::where('booking_id',$args['booking_id'])->where('refunded',0)->first();
                            $stripe = new \Stripe\StripeClient(getenv("STRIPE_SECRET"));
                            $result = $stripe->refunds->create([
                                    'charge' => $payments->charge_id,
                                ]);
                            $response['booking_id'] = $args['booking_id'];
                            $response['user_id'] = $payments->user_id;
                            $response['amount'] = $result->amount * 0.01;
                            $response['charge_id'] = $result->charge;
                            $response['refunded'] = 1;
                             $response['last4'] = $payments->last4;
                                $response['brand'] = $payments->brand;
                                $response['status'] = 'succeeded';
                        $payment = Payment::create($response);
                        } 
                    }
                $booking->status = config('booking-status.cancelled');
                $result = $booking->save();

                if(!empty($result)){
                                return [
                                        'status' => "SUCCESS",
                                        'message' => "Booking Cancelled Successfully",
                                    ];
                                }else {
                                    return [
                                        'status' => "FAIL",
                                        'message' => "fail",
                                    ];
                                }
                }elseif($booking->status != config('booking-status.cancelled') && ($booking->booking_type == "FREE" )) {
                        $booking->status = config('booking-status.cancelled');
                        $result = $booking->save();
                         return [
                                        'status' => "SUCCESS",
                                        'message' => "Booking Cancelled Successfully",
                                ];
                }else {
                    return [
                                        'status' => "FAIL",
                                        'message' => "Already cancelled",
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
                "booking_id" => "required",
                
            ]);
    }
}
