<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable20210623 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 150)->nullable();
            $table->string('image', 200)->nullable();
            $table->string('cover', 200)->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->longText('description', 200)->nullable();
            $table->boolean('private')->default('0')->nullable();
            $table->boolean('featured')->default('0')->nullable();
            $table->string('price_to_you', 150)->nullable();
            $table->string('price_to_student', 150)->nullable();
            $table->boolean('level')->default('1')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
}
