<?php

namespace App\GraphQL\Mutations;

use Auth;
use App\Entities\Blog;
use App\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Validation\ValidationException;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Http\UploadedFile;



class DeleteBlog {

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
                 $this->validator($args['data'])->validate();
                 $blog = Blog::findOrFail($data['blog_id']);
                 $result = $blog->delete();

                 if(!empty($result)) {
                             
                                return [
                                 'status' => 'SUCCESS',
                                    'message' => 'Blog Delete Successfully'

                                ];

                 }else {
                    return [
                     'status' => 'FAIL',
                        'message' => 'Fail to Delete Blog.'

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
                "blog_id" => "required"
            ]);
    }

    /*
     * Return  S3 Bucket Image Url.
     *
     */
    public function imageUploadToS3($base64Img) {

        $data = explode(";base64,", $base64Img);
        $image_type_aux = explode("image/", $data[0]);
        $image_type = $image_type_aux[1];
        $filename = time() . '.' . $image_type;
        $image = base64_decode($data[1]);
        // $url = config('filesystems.disks.s3.endpoint');
        $data = Storage::put($filename, $image);
        return $filename;
    }
}
