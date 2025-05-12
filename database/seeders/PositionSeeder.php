<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HRD\Position;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $positions = [
            'HR Manager',
            'Finance Staff',
            'IT Support',
            'Nurse',
            'Doctor',
            'Operator',
        ];

        foreach ($positions as $position) {
            Position::create(['name' => $position]);
        }
    }
}
