<?php
namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use Auth;
use App\Entities\User;
use Stripe\Stripe;
// use Laravel\Cashier\Billable;
// use Laravel\Cashier\Cashier;



class AddCard {

  public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
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

      // $data = $args['data'];
        try {
          $this->validator($args)->validate();
          return $this->generateStripeToken($args);
            $user_id = Auth::user()->id;
            $user = User::findOrFail($user_id);

            $options = array(
                    'email' => $user->email,
                    'source'  => $args['token']
                    
                );

            if(!empty($user->stripe_id)) {
                $stripe = new \Stripe\StripeClient(getenv("STRIPE_SECRET"));
                $response = $stripe->customers->createSource(
                                    $user->stripe_id,
                            ['source' => $args['token']]
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
            
            return [
                'status' => 'CARD_ADDED',
                'message' => "Card added successfully"
            ]; 
        } catch (ValidationException | ModelNotFoundException $e) {
              $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);  
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function generateStripeToken(array $data)
    {

          $stripe = new \Stripe\StripeClient(getenv("STRIPE_SECRET"));
        $data = $stripe->tokens->create([
          'card' => [
            'number' => '5555555555554444',
            // 'number' => '4242424242424242',
            'exp_month' => 10,
            'exp_year' => 2022,
            'cvc' => '214',
          ],
        ]);
        dd($data['id']);
        // $this->validator($data)->validate();
        // $user_id = Auth::user()->id;
        // $user = User::findOrFail($user_id);
        // return $this->stripeAction($data,$user);
        
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
                    'token' => "required"
        ]);
    }

    
    
}
