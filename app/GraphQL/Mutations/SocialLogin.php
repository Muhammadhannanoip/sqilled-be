<?php

namespace App\GraphQL\Mutations;

use App\Entities\User;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Exceptions\GraphQLValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Auth;
use DB;

/**
 * Class SocialLogin
 *
 * @package App\GraphQL\Mutations
 */
class SocialLogin
{
    /**
     * @param $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     * @param \GraphQL\Type\Definition\ResolveInfo|null $resolveInfo
     * @return mixed
     * @throws \App\Exceptions\GraphQLValidationException
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo = null)
    {
        try {
        	$this->validator($args)->validate();
            if($args['provider'] == 'FACEBOOK') {
	            try {
	                $userData = Socialite::driver($args['provider'])->fields([
	                    'first_name', 
	                    'last_name', 
	                    'email'
	                ])->userFromToken($args['token']);
	            }catch(\Exception $e) {
	                return [
	                    'status' => 'FAIL',
	                    'message' => 'Unable To Login',
	                ];
	            }
		            try {

		                    $user = User::where('provider', Str::lower($args['provider']))->where('provider_id', $userData->getId())->firstOrFail();
		                } catch (ModelNotFoundException $e) {
		                	$isEmailExist = User::where('email',$userData->getEmail())->exists();
		                	if($isEmailExist) {
				             	return [
		            				'status' => 'FAIL',
		            				'message' => 'Email Already Exists'
		        				];
		             		}
		                	// dd($isEmailExist);

		                	$user = User::create([
			                    'first_name' => $userData->user['first_name'],
			                    'last_name' => $userData->user['last_name'],
			                    'email' => $userData->getEmail(),
			                    'provider' => $args['provider'],
			                    'provider_id' => $userData->getId(),
			                    'password' => Hash::make(Str::random(16)),
			                    'avatar' => $userData->getAvatar()
		            		]);
		        	}
            }else {
	                try {
	                     $userData = Socialite::driver($args['provider'])->userFromToken($args['token']);
	                     // dd($userData);
	                }catch(\Exception $e) {
	                	// dd($e);
	                    return [
	                      'status' => 'FAIL',
	                      'message' => 'Unable To Login',
	                    ];
	                }
	           //      
	                try {
	                    $user = User::where('provider', Str::lower($args['provider']))->where('provider_id', $userData->getId())->firstOrFail();
	                }catch (ModelNotFoundException $e) { 
                        $isEmailExist = User::where('email',$userData->user['email'])->exists();
                         if($isEmailExist) {
                             return [
                             'status' => 'FAIL',
                             'message' => 'Email Already Exists'
                            ];
                         }
	                	$user = User::create([
		                    'first_name' => $userData->user['given_name'],
		                    'last_name' => $userData->user['family_name'],
		                    'email' => $userData->user['email'],
		                    'provider' => $args['provider'],
		                    'provider_id' => $userData->user['id'],
		                    'password' => Hash::make(Str::random(16)),
		                    'avatar' => $userData->user['picture']
	            		]);
	                }

                
            }

                
        Auth::onceUsingId($user->id);
        
        $tokens = (object) $user->getTokens();
        // dd($tokens);
        return [
            'status' => 'SUCCESS',
            'message' => 'Login Success',
            'access_token' => $tokens->access_token,
            'expires_in' => $tokens->expires_in,
            'token_type' => $tokens->token_type,
            'user' => $user
        ];
        } catch (ValidationException $e) {
        	dd($e);
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
           'provider' => ['required', 'string'],
            'token' => ['required', 'string'],
       
        ]);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function validateToken($data)
    {
        switch ($data['provider']) {
            case 'FACEBOOK':
            return $this->facebookLogin($data['access_token']);
            case 'LINKEDIN':
            return $this->linkedinLogin($data['access_token'], $data);
            case 'MANUAL':
            return $this->manualLogin($data);
        }
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function facebookLogin($accessToken)
    {
        $gateway = app(FacebookApi::class);
        $userInfo = $gateway->getUserByAccessToken($accessToken);
        try {
            $user = User::where('facebook_id', $userInfo['id'])->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $user = $this->register([
                'facebook_id' => $userInfo['id'],
                'name' => $userInfo['name'],
                'email' => $userInfo['email'],
                'password' => Str::random(16)
            ]);
        }
        return $user->getTokens();
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function linkedinLogin($accessToken, $data)
    {
        $gateway = app(LinkedinApi::class);
        $userInfo = $gateway->getUserByAccessToken($accessToken); 
        
        try {
            $userEmailDetails = $gateway->getUserEmailByAccessToken($accessToken);
            if(!empty($userEmailDetails)){
                $userEmailCollection = collect($userEmailDetails['elements']);
                $userEmailArr = $userEmailCollection->map(function ($item) {
                    return (array_key_exists("handle~",$item))?$item['handle~']['emailAddress']:FALSE;
                });
                $userEmail = (isset($userEmailArr[0]) && !empty($userEmailArr[0]))?$userEmailArr[0]:FALSE;
                
                if($userEmail == FALSE)
                    throw new GraphQLValidationException('', 'Sorry, you have not provided email address with LinkedIn. Please update your email address with LinkedIn Account in order to continue registration');
                
            } else {
                throw new GraphQLValidationException('', 'Sorry, you have not provided email address with LinkedIn. Please update your email address with LinkedIn Account in order to continue registration');
            }
        } catch (ValidationException | ModelNotFoundException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
        
        try {
            if(isset($data['claim_provider_id']) && !empty($data['claim_provider_id'])){
                $this->linkedinUnclaimValidator(['linkedin_id'=>$userInfo['id']])->validate();
                
                if(!empty($userEmail)){ $data['email'] = $userEmail; }
                
                $user_info = $this->updateProviderInfo($data, $userInfo);
                return $user_info->getTokens();
            } else {
                
                $this->linkedinValidator(['email'=>$userEmail, 'linkedin_id'=>$userInfo['id']])->validate();
                $provider = new UpdateProvider();

                try {
                    $user = User::where('linkedin_id', $userInfo['id'])->first();
                    if(!empty($user) && $user->hasRole("User")) {
                        if($user->hasRole("Provider")) {
                            throw new GraphQLValidationException($e->getMessage(), 'Provider already exists.' );
                        }

                        $user->name = $data['name'];
                        $user->email = $userEmail;
                        $user->trial_ends_at = now()->addDays(config('provider.default_trial_period_days'));
                        $user->status = config('provider.pending_approval');
                        $user->password = Hash::make($data['password']);
                        $user->save();
                        $user->removeRole('User');
                        $user->assignRole('Provider');
                    } else {
                        $user = User::where('email', $userEmail)->first();
                        if(empty($user) || is_null($user)) {
                            //save user details
                            $user = $this->register([
                                'linkedin_id' => $userInfo['id'],
                                'name' => $data['name'],
                                'email' => $userEmail,
                                'trial_ends_at' =>  now()->addDays(config('provider.default_trial_period_days')),
                                'password' => $data['password'],
                                'status' =>config('provider.pending_approval')
                            ]);
                        } else {
                            if($user->hasRole("Provider")) {
                                throw new GraphQLValidationException($e->getMessage(), 'Provider already exists with your linkedin email.' );
                            }

                            $user->name = $data['name'];
                            $user->email = $userEmail;
                            $user->linkedin_id = $userInfo['id'];
                            $user->trial_ends_at = now()->addDays(config('provider.default_trial_period_days'));
                            $user->status = config('provider.pending_approval');
                            $user->password = Hash::make($data['password']);
                            $user->save();
                            $user->removeRole('User');
                            $user->assignRole('Provider');    
                        }
                    }

                    $provider = Provider::where('user_id',$user->id)->first();
                    if(empty($provider) || is_null($provider)) {
                        $provider = new Provider();    
                    }
                    
                    //Create provider table record
                    $provider->user_id = $user->id;
                    $provider->company_name = $data['company_name'];
                    $provider->title = $data['title'];
                    $provider->show_name = $data['show_name'];
                    $provider->show_title = $data['show_title'];
                    $provider->engaged_provider = config('provider.not_engaged_provider');
                    $provider->save();
                    if(isset($data['photo_url']) && !empty($data['photo_url'])){
                      $provider->addMediaFromUrl($data['photo_url'])->toMediaCollection('photo_url');
                    }

                    //create user stripe account
                    $user->createAsStripeCustomer();
                    $user->save();
                    
                    $this->sendAccountVerifyEmail($user);
                    return $user->getTokens();

                } catch (ValidationException | ModelNotFoundException $e) {
                    throw new GraphQLValidationException($e->getMessage(), 'Somthing went wrong, please try agin later');
                }
            }
        } catch (ValidationException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function manualLogin($data)
    {
        
        try {
            if( isset($data['claim_provider_id']) && !empty($data['claim_provider_id']) ){
                $user = User::where('email', $data['email'])->firstOrFail();

                if(isset($data['email']) && !empty($data['email'])){
                    $user_info = $this->updateProviderInfo($data, $user);
                    return $user_info->getTokens();
                }      
            }else {
                $provider = new UpdateProvider();
                try {
                    $user = User::where('email', $data['email'])->first();
                    if(!empty($user) && $user->hasRole("User")) {
                        if($user->hasRole("Provider")) {
                            throw new GraphQLValidationException(" ", 'Provider already exists.' );
                        }

                        $user->name = $data['name'];
                        $user->email = $data['email'];
                        $user->trial_ends_at = now()->addDays(config('provider.default_trial_period_days'));
                        $user->status = config('provider.pending_approval');
                        $user->password = Hash::make($data['password']);
                        $user->save();
                        $user->removeRole('User');
                        $user->assignRole('Provider');
                    } else {
                        $user = User::where('email', $data['email'])->first();
                        if(empty($user) || is_null($user)) {
                            //save user details
                            $user = $this->register([
                                'linkedin_id' => null,
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'trial_ends_at' =>  now()->addDays(config('provider.default_trial_period_days')),
                                'password' => $data['password'],
                                'status' =>config('provider.pending_approval')
                            ]);
                        } else {
                            if($user->hasRole("Provider")) {
                                throw new GraphQLValidationException(" ", 'Provider already exists with your  email.' );
                            }

                            $user->name = $data['name'];
                            $user->email = $data['email'];
                            $user->linkedin_id = null;
                            $user->trial_ends_at = now()->addDays(config('provider.default_trial_period_days'));
                            $user->status = config('provider.pending_approval');
                            $user->password = Hash::make($data['password']);
                            $user->save();
                            $user->removeRole('User');
                            $user->assignRole('Provider');    
                        }
                    }

                    $provider = Provider::where('user_id',$user->id)->first();
                    if(empty($provider) || is_null($provider)) {
                        $provider = new Provider();    
                    }
                    
                    //Create provider table record
                    $provider->user_id = $user->id;
                    $provider->company_name = $data['company_name'];
                    $provider->title = $data['title'];
                    $provider->show_name = $data['show_name'];
                    $provider->show_title = $data['show_title'];
                    $provider->engaged_provider = config('provider.not_engaged_provider');
                    $provider->save();
                    if(isset($data['photo_url']) && !empty($data['photo_url'])){
                      $provider->addMediaFromUrl($data['photo_url'])->toMediaCollection('photo_url');
                    }

                    //create user stripe account
                    $user->createAsStripeCustomer();
                    $user->save();
                    
                    $this->sendAccountVerifyEmail($user);
                    return $user->getTokens();

                } catch (ValidationException | ModelNotFoundException $e) {
                    throw new GraphQLValidationException($e->getMessage(), 'Somthing went wrong, please try agin later');
                }
            }
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
        event(new Registered($user = $this->create($data)));
        if (!$user->hasRole('Provider')) {
            $user->assignRole('Provider');
        }
        return $this->registered($user);
    }

    /**
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
   

    protected function linkedinValidator(array $data) 
    {
        $message = ['email.required'=> 'Sorry, you have not provided email address with LinkedIn. Please update your email address with LinkedIn Account in order to continue registration','linkedin_id.unique'=>'Linkedin details already exists.','email.unique'=>'Your linkedin email already exists.'];
        return Validator::make($data, [
            'linkedin_id' => 'required',
            'email' => 'required',
        ],$message);
    }
    
    protected function linkedinUnclaimValidator(array $data) 
    {
        $message = ['email.required'=> 'Sorry, you have not provided email address with LinkedIn. Please update your email address with LinkedIn Account in order to continue registration','linkedin_id.unique'=>'Linkedin details already exists.'];
        return Validator::make($data, [
            'linkedin_id' => 'required',
        ],$message);
    }

    /**
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function create(array $data)
    {
        return User::create($data);
    }

    /**
     * @param $user
     * @return mixed
     */
    protected function registered($user)
    {
        return $user;
    }

    public function updateProviderInfo($data, $userInfo = '')
    {
        try {
            $provider = Provider::findOrFail($data['claim_provider_id']);
            $user = User::findOrFail($provider->user_id);
            if($user->status != config('provider.unclaimed_provider')) {
                throw new GraphQLValidationException($e->getMessage(), 'This Profile already claimed another one.' );
            }
            if($userInfo->contains('email')){
                $mainUser = User::where('email', $userInfo['email'])->first();
            }else{
                $mainUser = User::where('linkedin_id', $userInfo['id'])->first();
            }
            if(!empty($mainUser) && $mainUser->hasRole("User")) {
                if($mainUser->hasRole("Provider")) {
                    throw new GraphQLValidationException($e->getMessage(), 'Provider already exists with email details.' );
                }

                Review::where('review_given_by',$mainUser->id)->update(array('review_given_by'=>$user->id));
                $mainUser->linkedin_id = null;
                $mainUser->email = null;
                $mainUser->save();
            }
            
            if(isset($data['photo_url']) && !empty($data['photo_url'])){
                $provider_mutation_ref = new UpdateProvider();
                $photo_url = $provider_mutation_ref->saveCompanyLogo($data['photo_url']);
                $user['photo_url'] = $photo_url;
            }
            if(isset($data['email']) && !empty($data['email'])){
                $user['email'] = $data['email'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $user['password'] = bcrypt($data['password']);
            }
            $user['linkedin_id'] = $userInfo['id'];
            $user['name'] = $data['name'];
            $user['trial_ends_at'] = now()->addDays(config('provider.default_trial_period_days'));
            $user['status'] = config('provider.email_verification_pending'); //For Email verification pending
            $user->createAsStripeCustomer();
            $user->save();
            
            $provider['company_name'] = $data['company_name'];
            $provider['title'] = $data['title'];
            $provider['show_name'] = $data['show_name'];
            $provider['show_title'] = $data['show_title'];
            $provider['engaged_provider'] = config('provider.not_engaged_provider');
            $provider->save();
            $this->sendAccountVerifyEmail($user);

            if(isset($mainUser) && !empty($mainUser)) {
                $mainUser->forceDelete();
            }
            
            return $user;
        } catch (ValidationException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
    }
    
    private function sendAccountVerifyEmail($user = false){
        
        if(empty($user)){ return false; }
        $mailData = [];
        $verify_token = Crypt::encryptString(config('mail.data.header_encrypt_token').$user->linkedin_id);
        $encrypted_email = Crypt::encryptString(config('mail.data.header_encrypt_token').$user->email);
        
        try {
            $logo_url = MarketingSiteContent::first()->value('logo_url');
            $mailData['subject'] = "Almost done! Confirm your OnCall Discovery credentials";
            $mailData['logo_url'] = $logo_url;
            $mailData['tips_url'] = config('mail.data.login_tips_url');
            $mailData['user_name'] = $user->name;
            $mailData['review_new_search_url'] = config('mail.data.review_new_search_url');
            $mailData['confirm_account_verification_link'] = config('mail.data.account_verify_confirm_link').'/'.$verify_token.'/'.$encrypted_email;
            Mail::to($user)->send(new UserAccountVerificationMail($mailData));

            try {
                $this->updateMailChimpTags($user);
            } catch (\Exception $ex) { 
                
            }
        } catch (\Exception $ex) { 
            
        }
    }
    
    public function confirmAccountEmailVerification($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo){
        
        try {
            $confirm_data = $args['data'];
            $this->verfiyEmailValidator($confirm_data)->validate();
            
            $decrypted_trasfer_email = trim(str_replace(config('mail.data.header_encrypt_token'),'',Crypt::decryptString($confirm_data['email'])));
            $decrypted_trasfer_token= trim(str_replace(config('mail.data.header_encrypt_token'),'',Crypt::decryptString($confirm_data['token'])));
            $user_id = User::where('linkedin_id',$decrypted_trasfer_token)->where('email',$decrypted_trasfer_email)->value('id');
            $user_req = User::find($user_id);
            if(is_null($user_req)){ return $this->invalidEmailRequest(); }
            
            $user_req->status = config('provider.pending_approval');
            $user_req->save();
            return $this->emailVerifiedSuccess();
           
        } catch (ValidationException | ModelNotFoundException $e) {
            $exception = $e->errors();
            $exception_keys = array_keys($exception);
            throw new GraphQLValidationException($e->errors(), $exception[$exception_keys[0]][0]);
        }
    }

    private function updateMailChimpTags($user = [], $tag_mailchimp_subscription = 'Trial OCD'){

        if(!empty($user)){
            $mailChimpTagsNew = [];

            if(!Newsletter::hasMember($user->email)){
                $provider = Provider::where('user_id',$user->id)->first();
                $company_name = isset($provider->company_name) ? $provider->company_name : '';
                Newsletter::subscribe($user->email, ['FNAME'=>$user->name,'COMPANY'=>$company_name]);
                array_push($mailChimpTagsNew, $tag_mailchimp_subscription,'Pending Reviewer');
            } else {
                $mailChimpTags = Newsletter::getTags($user->email);
                
                $tag_search_name_add = $tag_mailchimp_subscription;
                $tag_search_name_remove = (strcasecmp('Trial OCD', $tag_mailchimp_subscription) == 0)?'Paid OCD':'Trial OCD';
                if (isset($mailChimpTags) && !empty($mailChimpTags) && isset($mailChimpTags['tags']) && !empty($mailChimpTags['tags'])) {
                    if (FALSE  !== array_search($tag_search_name_remove, array_column($mailChimpTags['tags'], 'name'))) {
                        Newsletter::removeTags([$tag_search_name_remove], $user->email);
                    }
                    if (FALSE == array_search($tag_search_name_add, array_column($mailChimpTags['tags'], 'name'))) {
                        array_push($mailChimpTagsNew, $tag_search_name_add);
                    }
                }
            }
            Newsletter::addTags($mailChimpTagsNew, $user->email);
        }
    }
    
    /**
     * @return array
     */
    protected function verfiyEmailValidator(array $data)
    {
        return Validator::make($data, [
            "token" => "required",
            "email" => "required",
        ]);
    }
    
    /**
     * @return array
     */
    protected function invalidEmailRequest(){
        return [
            'status' => 'ACCOUNT_EMAIL_VERIFICATION_FAIL',
            'message' => 'Invalid Request, please contact Oncall Discovery Team'
        ];
    }
    
    /**
     * @return array
     */
    protected function emailVerifiedSuccess()
    {
        return [
            'status' => 'ACCOUNT_EMAIL_VERIFICATION_SUCCESS',
            'message' => 'Thanks ! For email verification Oncall team will verify your account.'
        ];
    }
    
}