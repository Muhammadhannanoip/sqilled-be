<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use App\Entities\User;
use App\Entities\EmailSubscription;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Carbon;
use App\Entities\Payment;

class SaveEmailSubscription {

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
            // dd($args['email']);
                // $data = $args['email'];
                $data = $args['data'];


                $loggedin_user_id  = Auth::user()->id;
                $this->validator($data)->validate();
                $user = User::findOrFail($loggedin_user_id);
                // $author = User::findOrFail($data['author_id']);
                if(isset($data['token']) && !empty($data['token'])) {
                    //check user stripe
                    $options = array(
                    'email' => $user->email,
                    'source'  => $data['token']
                    
                );

                        if(!empty($user->stripe_id)) {
                            $stripe = new \Stripe\StripeClient(getenv("STRIPE_SECRET"));
                            $response = $stripe->customers->createSource(
                                                $user->stripe_id,
                                        ['source' => $data['token']]
                                    );
                            // make new card as default payment
                            $customer = $stripe->customers->retrieve(
                                        $user->stripe_id,
                                        []
                                      );
                                    $customer->default_source=$response['id'];
                                    $customer->save();  

                 
                        }else {
                                $stripeCustomer = $user->createAsStripeCustomer($options);
                                $user->card_brand = $stripeCustomer['sources']['data'][0]['brand'];
                                $user->card_last_four = $stripeCustomer['sources']['data'][0]['last4'];
                                $user->save();         
                        }
                }
                 $user = User::findOrFail($loggedin_user_id);
                 if (!empty($user->stripe_id) && $user->hasDefaultPaymentMethod()) {
                        $paymentMethod = $user->defaultPaymentMethod();
                        if($paymentMethod['object'] == 'card') {
                            $charge_amount  = config('email-subscription.amount')*100;
                            $stripeCharge = $user->charge($charge_amount, $paymentMethod);
                            $email_subscription_data = [
                                'user_id' => $loggedin_user_id,
                                'email' => $data['email'],
                                'author_id' => $data['author_id'],
                                'charge_amount' =>  config('email-subscription.amount')
                            ];
                            // dd($email_subscription_data);
                            $result = EmailSubscription::create($email_subscription_data);

                            $response = [];
                            $response['charge_id'] = $stripeCharge->charges['data'][0]['id'];
                            $response['amount'] = (($stripeCharge->amount) * 0.01);
                            $response['user_id'] = $loggedin_user_id;
                            $response['booking_id'] = null;

                            $response['last4'] = isset($stripeCharge->charges['data'][0]['payment_method_details']['card']['last4']) ? $stripeCharge->charges['data'][0]['payment_method_details']['card']['last4']:'';
                            $response['brand'] = isset($stripeCharge->charges['data'][0]['payment_method_details']['card']['brand']) ? $stripeCharge->charges['data'][0]['payment_method_details']['card']['brand']:'';
                            $response['status'] = $stripeCharge->charges['data'][0]['status'];

                            $payment = Payment::create($response);
                             if(!empty($payment)){
                                return [
                                        'status' => "SUCCESS",
                                        'message' => "Subscription Done successfully",
                                    ];
                                }else {
                                    return [
                                        'status' => "FAIL",
                                        'message' => "Subscription fail",
                                    ];
                                }
                            }else {
                                return [
                                    'status' => "FAIL",
                                    'message' => "Add Credit Card first to start subscription.",
                                ];
                            }
                        
                        
                 }else {
                    return [
                            'status' => "FAIL",
                            'message' => "Add Credit Card first to start subscription.",
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
                "email" => "required",
                "author_id" => "required"
            ]);
    }
}
