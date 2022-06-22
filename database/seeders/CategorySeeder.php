<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $imageName = 'images/categories/';
        $name      = array('fitness' => $imageName . 'fitness.png', 'Yoga' => $imageName . 'yoga.png', 'Aerial' => $imageName . 'aerial.png', 'Dance' => $imageName . 'dance.png', 'HIIT' => $imageName . 'hiit.png', 'Art' => $imageName . 'art.png');

        foreach ($name as $key => $value) {

            DB::table('categories')->insert([
                'name'  => $key,
                'image' => $value,
            ]);
        }
    }
}
