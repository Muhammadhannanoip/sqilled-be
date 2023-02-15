<?php

namespace App\GraphQL\Mutations;

use Auth;
use App\Entities\Blog;
use App\Entities\User;
use Illuminate\Http\Request;
use App\Entities\BlogComment;
use Illuminate\Support\Facades\Storage;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\GraphQLValidationException;

use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Http\UploadedFile;



class BlogCommentAdd
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
        try {

            $data = $args['data'];
            $this->validator($args['data'])->validate();
            $blog_comment = new BlogComment;
            $blog_comment->author_id = $data['author_id'];
            $blog_comment->blog_id = $data['blog_id'];
            $blog_comment->comment = $data['comment'];
            $result = $blog_comment->save();

            if (!empty($result)) {

                return [
                    'status' => 'SUCCESS',
                    'message' => 'Comment Add Successfully'

                ];
            } else {
                return [
                    'status' => 'FAIL',
                    'message' => 'Fail to Add Comment.'

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
            "author_id" => "required",
            "blog_id" => "required",
            "comment" => "required",
        ]);
    }

}
