<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $imageName = 'images/languages/';
        $name      = array('Beginner', 'Intermediate', 'Advance');

        foreach ($name as $key => $value) {

            DB::table('levels')->insert([
                'name' => $value,
            ]);
        }
    }
}
