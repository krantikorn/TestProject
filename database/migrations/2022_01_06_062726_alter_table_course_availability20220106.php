<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCourseAvailability20220106 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_availability', function (Blueprint $table) {
            $table->string('parent_id')->nullable()->after('course_id');
            $table->string('start_date')->nullable()->after('when');
            $table->string('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_availability', function (Blueprint $table) {
            $table->drop('parent_id');
            $table->drop('start_date');
            $table->drop('end_date');
        });
    }
}
