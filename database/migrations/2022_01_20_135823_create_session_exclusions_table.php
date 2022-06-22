<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionExclusionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('availability_id');
            $table->foreign('availability_id')->references('id')->on('course_availability')->onDelete('cascade');
            $table->string('exclusions', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_exclusions');
    }
}
