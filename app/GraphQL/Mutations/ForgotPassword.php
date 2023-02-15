<?php
namespace App\GraphQL\Mutations;

use Illuminate\Support\Facades\Hash;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Joselfonseca\LighthouseGraphQLPassport\GraphQL\Mutations;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Joselfonseca\LighthouseGraphQLPassport\Exceptions\ValidationException as GraphQLValidationException;

class ForgotPassword
{ 
//    use SendsPasswordResetEmails;
    /**
     * @param $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return array
     * @throws \Exception
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {   
        $response = $this->broker()->sendResetLink(['email' => $args['email']]);

        if ($response == Password::RESET_LINK_SENT) {
            return [
                'status' => 'EMAIL_SENT',
                'message' => trans($response)
            ];
        } else {
               return [
                'status' => 'EMAIL_NOT_SENT',
                'message' => trans($response)
            ]; 
        }

        
    }

}
