<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\GraphQLValidationException;
use Illuminate\Support\Facades\Validator;
use App\Entities\User;
use DB;

class SearchAuthor
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

        // $searchQry  = User::where(DB::raw("CONCAT('first_name',' ','last_name')"), 'LIKE', '%' .$args['keyword'] . '%')->orWhere('tag_line', 'LIKE', '%' .$args['keyword'] . '%')->get(); 
        $searchQry  = User::where('first_name', 'LIKE', '%' .$args['keyword'] . '%')->where('type','W')->orWhere('tag_line', 'LIKE', '%' .$args['keyword'] . '%')->get(); 
        return $searchQry;
    }

    // public function getAuthors ($root, array $args) {
    //      $searchQry  = User::where('first_name', 'LIKE', '%' .$args['keyword'] . '%')->where('type','W')->orWhere('tag_line', 'LIKE', '%' .$args['keyword'] . '%')->orWhere('experience', 'LIKE', '%' .$args['keyword'] . '%')->orWhere('certification', 'LIKE', '%' .$args['keyword'] . '%')->orWhere('city', 'LIKE', '%' .$args['keyword'] . '%')->orWhere('state', 'LIKE', '%' .$args['keyword'] . '%')->orWhere('country', 'LIKE', '%' .$args['keyword'] . '%'); 
    //      return $searchQry;
    // }

    public function getAuthors ($root, array $args) {
        $a = ($args['keyword']);
        $searchQry = User::search($a);
        return $searchQry;


    }
}
