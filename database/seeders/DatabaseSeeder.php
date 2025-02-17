<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class, // استدعاء السيدر الخاص بالأدمن
            DoctorSeeder::class, 
        ]);

        User::factory(10)->create(); // إنشاء مستخدمين
        // Doctor::factory(5)->create(); // إنشاء أطباء
        // Patient::factory(5)->create(); // إنشاء مرضى
        // Appointment::factory(20)->create(); 
    }
}
