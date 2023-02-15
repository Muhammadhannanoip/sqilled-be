<?php
 
namespace App\GraphQL\Mutations;

use App\Entities\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Joselfonseca\LighthouseGraphQLPassport\GraphQL\Mutations\BaseAuthResolver;
use Carbon\Carbon; 
use Illuminate\Support\Str;

/**
 * Class SignUp
 *
 * @package App\GraphQL\Mutations
 */
class Register extends BaseAuthResolver
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
        
        try {
            return $this->register($args);
        } catch (ValidationException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
    } 

    /**
     * @param array $data
     * @return array
     */
    public function register(array $data)
    { 
            $this->validator($data)->validate();
            $user = $this->create($data);
            if($user) {
                return $this->registered($user);
            } else {
                throw new GraphQLValidationException('Error', 'Somthing went wrong, please try again later');
            }
        
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $messages = [
            'email.unique'    => 'Email already exists.',
        ];
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:6'],
            'type'  => ['required']
        ],$messages);
    }

    /**
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function create(array $data)
    {
        $user = new User;
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->type = $data['type'];
        $user->password = Hash::make($data['password']);
        $user->save();
        if($user->id) {
            return [
                'status' => 'SUCCESS',
                'message' => "Register Successful"
            ];
        }else {
            return [
                'status' => 'FAIL',
                'message' => "Registration Fail"
            ];
        }
        // $user = user::find($user->id);
        // $tokens = (object) $user->getTokens();
        // $response['token_type'] = $tokens->token_type;
        // $response['access_token'] = $tokens->access_token;
        // $response['expires_at'] = $tokens->expires_in;
        // $response['user'] = $user;

        // return $response;
    }

    /**
     * @param $user
     * @return array
     */
    protected function registered($user)
    {
        return [
            'status' => 'USER_REGISTERED',
            'message'=> 'User Register Successful',
            // 'token_type'=> $user['token_type'],
            // 'access_token'=> $user['access_token'],
            // 'expires_in'  => $user['expires_at'],
            // 'user' => $user['user']
        ];
    }
}
