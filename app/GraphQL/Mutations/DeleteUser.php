<?php

namespace App\GraphQL\Mutations;

use DB;
use Auth;
use App\Entities\Blog;
use App\Entities\User;
use App\Entities\Article;
use App\Entities\Booking;
use App\Entities\Payment;
use App\Entities\VideoCallRoom;
use App\Entities\EmailSubscription;
use Illuminate\Support\Facades\Storage;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteUser {

    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param  mixed[]  $args The arguments that were passed into the field.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context Arbitrary data that is shared between all fields of a single query.
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     * @return mixed
     */
    // public function resolve($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) {
    //     try {
    //             $loggedin_user_id  = Auth::user()->id;
    //             $user = User::findOrFail($loggedin_user_id);
    //             // $user = User::findOrFail($args['id']);
                
    //             $user->delete();
    //             return [
    //                 'status' => 'User_Deleted',
    //                 'message' => 'Use Delete Successfully'

    //             ];
            
    //     } catch (ValidationException | ModelNotFoundException $e) {
    //         $exception = $e->errors();
    //         $exception_keys = array_keys($exception);
    //         throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
    //     }
    // }
    public function resolve($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) {
        try {


                $loggedin_user_id  = Auth::user()->id;
                $user = User::findOrFail($loggedin_user_id);
                try {
                    if(file_exists(public_path('image/'.$user->video_url))){
                        unlink(public_path('image/'.$user->video_url));
                    }
                    if(file_exists(public_path('image/'.$user->profile_image))){
                        unlink(public_path('image/'.$user->profile_image));
                    }
                       }catch(\Exception $e){
                        Log::info($e->getMessage());
                  }
                $user->delete();
                Article::where('user_id',$loggedin_user_id)->get();
                Blog::where('user_id',$loggedin_user_id)->get();
                Booking::where('user_id',$loggedin_user_id)->get();
                EmailSubscription::where('user_id',$loggedin_user_id)->get();
               // DB::table('subscriptions')->where('user_id',$loggedin_user_id)->get();
                DB::table('subscriptions')->where('user_id', $loggedin_user_id)->delete();
                Payment::where('user_id',$loggedin_user_id)->get();
                VideoCallRoom::where('user_id',$loggedin_user_id)->get();

                return [
                    'status' => 'User_Deleted',
                    'message' => 'User Delete Successfully!'

                ];

        } catch (ValidationException | ModelNotFoundException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
    }
}
