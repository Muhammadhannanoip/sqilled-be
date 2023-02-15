<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use App\Entities\User;
use Illuminate\Support\Facades\Validator;
use Auth;
use App\Entities\Article;


class SaveArticle {

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
                 $data = $args['data'];
                 $loggedin_user_id  = Auth::user()->id;
                 $this->validator($data)->validate();
                 $user = User::findOrFail($loggedin_user_id);
                 $data['user_id'] = $loggedin_user_id;
                 $result = Article::Create($data);
                 if(!empty($result)) {
                             
                                return [
                                 'status' => 'SUCCESS',
                                    'message' => 'Article Saved Successfully'

                                ];

                 }else {
                    return [
                     'status' => 'FAIL',
                        'message' => 'Fail to save.'

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
                "title" => "required",
                "content" => "required",
                "published_date" => "required"
            ]);
    }
}
