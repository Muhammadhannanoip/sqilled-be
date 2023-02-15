<?php
namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use App\Entities\User;
use App\Entities\UserTopicOfInterest;
use Hash;
use Auth;
use Illuminate\Support\Facades\Storage;




class EditProfile {

    

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
            $this->validator($data)->validate();
            $loggedin_user_id  = Auth::user()->id;
            $user = User::findOrFail($loggedin_user_id);

                //save user details
                 if(isset($data['profile_image'])) {
                    $imageUrl = empty($data['profile_image']) ? '' : $this->imageUploadToS3($data['profile_image']);
                    $user->avatar = $imageUrl;
                 }
                 $user->first_name = $data['first_name'];
                 $user->last_name = $data['last_name'];
                 $user->city = isset($data['city'])? $data['city']:null;
                 $user->state = isset($data['state'])? $data['state']:null;
                 $user->country = isset($data['country'])? $data['country']:null;
                 $user->tag_line = isset($data['tag_line'])? $data['tag_line']:null;
                 $user->qualification = isset($data['qualification'])? $data['qualification']:null;
                 $user->certification = isset($data['certification'])? $data['certification']:null;
                 $user->experience = isset($data['experience'])? $data['experience']:null;
                 $user->hourly_rate = isset($data['hourly_rate'])? $data['hourly_rate']:null;
                 $user->min_hourly_rate = isset($data['min_hourly_rate'])? $data['min_hourly_rate']:null;
                 $user->max_hourly_rate = isset($data['max_hourly_rate'])? $data['max_hourly_rate']:null;
                 // $user->topic_id = isset($data['topic_id'])? $data['topic_id']:null;
                 $user->hourly_rate = !empty($data['hourly_rate'])? $data['hourly_rate']:null;
                 $user->email = $data['email'];
                 $result = $user->save();
                 if(isset($data['topic_ids']) && !empty($data['topic_ids']) && is_array($data['topic_ids'])) {
                    UserTopicOfInterest::where('user_id', $loggedin_user_id)->delete();
                    foreach ($data['topic_ids'] as $key => $value) {

                        UserTopicOfInterest::create(['user_id' => $loggedin_user_id,'topic_id' => $value]);
                    }
                 }
                 return $user;
            
        } catch (ValidationException $e) {
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
                "first_name" => "required",
                "last_name" => "required",
                "email" => "required",
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
        $path = Storage::disk('s3')->put($filename, $image);
        $data = Storage::disk('s3')->url($path);
        $filename = config('filesystems.disks.s3.url').'/'.$filename;
        // $url = config('filesystems.disks.s3.url');
        // $data = Storage::put($filename, $image);
        return $filename;
    }
}
