<?php
 
namespace App\GraphQL\Mutations;

// use App\User;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Http\Request;
use Joselfonseca\LighthouseGraphQLPassport\Exceptions\AuthenticationException;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Joselfonseca\LighthouseGraphQLPassport\GraphQL\Mutations\BaseAuthResolver;
use App\Entities\User;
use Auth;

/**
 * Class SignUp
 *
 * @package App\GraphQL\Mutations
 */
class Login extends BaseAuthResolver
{
    
    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param  array  $args The arguments that were passed into the field.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null  $context Arbitrary data that is shared between all fields of a single query.
     * @param  \GraphQL\Type\Definition\ResolveInfo|null  $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     * @throws GraphQLValidationException
     * @return mixed
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo = null)
    {
    	
        $credentials = $this->buildCredentials($args);
            
        $response = $this->makeRequest2($credentials);
        $user = [];

        if (Auth::attempt(['email' => $args['username'], 'password' => $args['password']])) {
            $user = Auth::user();
            if(isset($args['device_token']) && !empty($args['device_token'])) {
                $this->updateDeviceToken($user->id,$args['device_token']);
            }

            if(isset($args['time_zone']) && !empty($args['time_zone'])) {
                $this->updateUserTimeZone($user->id,$args['time_zone']);
            }
        }

        $response['user'] = $user;
        return $response;
    }

    public function makeRequest2(array $credentials)
    {
        $request = Request::create('oauth/token', 'POST', $credentials, [], [], [
            'HTTP_Accept' => 'application/json',
        ]);
        $response = app()->handle($request);
        $decodedResponse = json_decode($response->getContent(), true);
        if ($response->getStatusCode() != 200) {
            throw new GraphQLValidationException(__('Authentication exception'), __('Incorrect username or password'));
        }

        return $decodedResponse;
    }

    public function updateDeviceToken($user_id,$device_token)
    {
       User::where('id',$user_id)->update(['device_token' => $device_token]);
    }

    // Update user time zone
    public function updateUserTimeZone($user_id,$time_zone)
    {
       User::where('id',$user_id)->update(['time_zone' => $time_zone]);
    }
}
