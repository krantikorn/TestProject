<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $imageName = 'images/languages/';
        $name      = array('english' => $imageName . 'english.png', 'french' => $imageName . 'french.png', 'spanish' => $imageName . 'spanish.png');

        foreach ($name as $key => $value) {

            DB::table('languages')->insert([
                'name'  => $key,
                'image' => $value,
            ]);
        }
    }
}
