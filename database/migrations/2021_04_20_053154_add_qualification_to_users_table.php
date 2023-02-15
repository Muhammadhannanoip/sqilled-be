<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQualificationToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->after('email')->nullable();
            $table->string('country')->after('email')->nullable();
            $table->longText('tag_line')->after('email')->nullable();
            $table->longText('qualification')->after('email')->nullable();
            $table->longText('certification')->after('email')->nullable();
            $table->longText('experience')->after('email')->nullable();
            $table->float('hourly_rate', 8,2)->after('email')->nullable();
            $table->String('type', 1)->default('W')->comment('W-writer,R-reader')->after('email')->nullable();
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
