<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use App\Entities\User;
use App\Entities\EmailSubscription;
use DB;
use Auth;

class GetEmailSubscription
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
        $user_type = $args['user_type'];
        $loggedin_user_id  = Auth::user()->id;
        $user = User::findOrFail($loggedin_user_id);
        if($user_type == "AUTHOR") {
            
                $result =  EmailSubscription::where('author_id',$loggedin_user_id)->get();
        }else {
                $result =  EmailSubscription::where('user_id',$loggedin_user_id)->get();
        }
          return $result;
    }
}
