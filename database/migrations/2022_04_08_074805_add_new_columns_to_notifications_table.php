<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('other_user_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('course_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('availability_id')->nullable()->after('user_id');
            $table->dateTime('when')->nullable()->after('user_id');
            $table->foreign('other_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('availability_id')->references('id')->on('course_availability')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('other_user_id');
            $table->dropColumn('course_id');
            $table->dropColumn('availability_id');
            $table->dropColumn('when');
        });
    }
}
