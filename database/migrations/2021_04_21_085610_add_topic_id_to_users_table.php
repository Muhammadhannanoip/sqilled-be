<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTopicIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
             $table->bigInteger('topic_id')->unsigned()->after('hourly_rate')->nullable();
             $table->float('min_hourly_rate', 8,2)->after('hourly_rate')->nullable();
             $table->float('max_hourly_rate', 8,2)->after('hourly_rate')->nullable();
             $table->foreign('topic_id')->references('id')->on('topic_of_interests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
