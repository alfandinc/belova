<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VisitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Pasien IDs from 100001 to 100010
        $pasienIds = range(100001, 100010);

        // Initialize the queue number starting from 2
        $queueNumber = 2;

        // Insert 10 records into the visitation table
        foreach ($pasienIds as $pasienId) {
            DB::table('erm_visitations')->insert([
                'id' => $this->generateRandomId(),  // Generate a random 21-digit ID
                'pasien_id' => $pasienId, // Use pasien_id from 100001 to 100010
                'metode_bayar_id' => 1,  // Assuming metode_bayar_id 1 is a valid entry
                'dokter_id' => 1,  // Assuming dokter_id 1 is valid
                'progress' => 2,  // As requested
                'status_dokumen' => 'asesmen',  // Static value 'asesmen'
                'tanggal_visitation' => Carbon::today()->format('Y-m-d'),  // Current date
                'no_antrian' => $queueNumber,  // Queue number, starts from 2
                'created_at' => Carbon::now(),  // Current date and time
                'updated_at' => Carbon::now()   // Current date and time
            ]);

            $queueNumber++;  // Increment queue number for the next iteration
        }
    }

    /**
     * Generate a random 21-digit number as a string.
     *
     * @return string
     */
    private function generateRandomId()
    {
        // Generate a 21-digit random number as a string
        return strval(mt_rand(1000000000, 9999999999) . mt_rand(1000000000, 9999999999));  // Combine two 10-digit random numbers
    }
}
