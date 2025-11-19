<?php

use Illuminate\Database\Seeder;

class CarsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conn = app('registry')->getConnection('default');
        $conn->insert('cars', ['id'=> 3]);
    }
}
