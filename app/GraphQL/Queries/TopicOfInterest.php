<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use App\Entities\UserTopicOfInterest;
use App\Entities\TopicOfInterest as TopicOfInterestModel;
use DB;

class TopicOfInterest
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

    }

    public function getUserTopic ($root, array $args) {
        // dd($root->id);
        $topic_ids = UserTopicOfInterest::where('user_id',$root->id)->pluck('topic_id')->toArray();
        // dd($topic_ids);
        $data =  TopicOfInterestModel::whereIn('id',$topic_ids)->get();
        // dd($data);
        return $data;
        // dd($topic_ids);
         
    }
}
