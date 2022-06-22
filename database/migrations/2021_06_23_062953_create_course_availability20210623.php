<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseAvailability20210623 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->dateTime('when')->nullable();
            $table->string('repeats', 200)->nullable();
            $table->string('slots', 100)->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_availability');
    }
}
