<?php

use Illuminate\Database\Seeder;

class TopicSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // App\Entities\TopicOfInterest::query()->truncate();
        App\Entities\TopicOfInterest::create([
            'name' => 'Trading'
        ]);

        App\Entities\TopicOfInterest::create([
            'name' => 'Derivatives
'
        ]);

        App\Entities\TopicOfInterest::create([
            'name' => 'Investment management advise'
        ]);

        App\Entities\TopicOfInterest::create([
            'name' => 'Financial consultancy'
        ]);

        App\Entities\TopicOfInterest::create([
            'name' => ' Management consultancy'
        ]);

       App\Entities\TopicOfInterest::create([
            'name' => 'Business growth'
        ]);
        
        
    }
}
