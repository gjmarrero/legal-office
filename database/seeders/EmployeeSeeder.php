<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('employees')->insert(
            [
                [
                    'emp_name' => 'Sunny Sacla',
                    'emp_position' => 'Provincial Legal Officer',
                ],
                [
                    'emp_name' => 'Mary Ann Pastor',
                    'emp_position' => 'Administrative Aide'
                ],
                [
                    'emp_name' => 'Jay-jay Daliones',
                    'emp_position' => 'Legal Assistant',
                ],
            ],
        );
    }
}
