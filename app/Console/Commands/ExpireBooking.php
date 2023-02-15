<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Entities\ScheduleTrip;
use Carbon\carbon;
use DateTime;
use DateTimeZone;
use App\Entities\User;
use App\Entities\Booking;
use App\Entities\Payment;
use App\Mail\VerifyEmail;
use Mail;


class ExpireBooking extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-booking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire Booking';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        // try{
        //     Mail::to('iamfreefrank@gmail.com')->send(new VerifyEmail("hhtps:www.google.com",'ranjit'));

        // }catch(\Exception $e) {
        //     Mail::to('ranjitraidx@gmail.com')->send(new VerifyEmail("hhtps:www.google.com",$e));
        // }

        $result = Booking::select('*')
                                
                                    // ->whereBetween('booking_date', [$from, $to])
                                    // ->where('notification_sent',0)
                                    ->where('status',config('booking-status.pending'))
                                    ->get()->toArray(); 
                                    // dd($result);

                foreach ($result as $key => $value) {
            
                    $user = User::find($value['user_id']);
                    if(!empty($user->time_zone)) {
                        $time_zone = $user->time_zone;
                    }else {
                        $time_zone = 'UTC';
                    }
                    $date = new DateTime("now", new DateTimeZone($time_zone) );
                    $user_current_date = $date->format('Y-m-d H:i:s');
                    $user_booking_date = new DateTime($value['booking_date'].' '.$value['start_time'], new DateTimeZone($time_zone) );
                    $user_booking_date->modify('+5 minutes');
                    $user_booking_date = $user_booking_date->format('Y-m-d H:i:s');
                    $current_date = strtotime($user_current_date); //gives value in Unix Timestamp (seconds since 1970)
                    $booking_date = strtotime($user_booking_date);

                    if($current_date <  $booking_date) {
                       // do nothing
                    }else {

                        // cancell booking
                        $booking = Booking::findOrFail($value['id']);
                        try{
                            if($booking->is_user_join) {
                                $payments = Payment::where('booking_id',$value['id'])->where('refunded',0)->first();
                                $stripe = new \Stripe\StripeClient(getenv("STRIPE_SECRET"));
                                $result = $stripe->refunds->create([
                                        'charge' => $payments->charge_id,
                                    ]);
                                $response['booking_id'] = $value['id'];
                                $response['user_id'] = $payments->user_id;
                                $response['amount'] = $result->amount * 0.01;
                                $response['charge_id'] = $result->charge;
                                $response['refunded'] = 1;
                                $response['last4'] = $payments->last4;
                                $response['brand'] = $payments->brand;
                                $response['status'] = 'succeeded';
                                $payment = Payment::create($response);
                            }
                        }catch(\Exception $e){

                        }
                        
                        Booking::where('id',$value['id'])->update(['status' => 3]);
                    }

                }


                    
                    }
    }

