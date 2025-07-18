<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        if (!\App\Models\User::where('email', 'sasees@admin.com')->exists()) {
    \App\Models\User::factory()->create([
        'name' => 'Adminsasees',
        'email' => 'sasees@admin.com',
        'role_type' => 'Admin',
        'password' => Hash::make('naturalAdmin@sasees'),
    ]);
}

if (!\App\Models\User::where('email', 'chaminda@admin.com')->exists()) {
    \App\Models\User::factory()->create([
        'name' => 'Adminchaminda',
        'email' => 'chaminda@admin.com',
        'role_type' => 'Admin',
        'password' => Hash::make('naturalAdmin@chaminda'),
    ]);
}




if (!\App\Models\User::where('email', 'chaminda@admin.com')->exists()) {
    \App\Models\User::factory()->create([
        'name' => 'Cashierchaminda',
        'email' => 'chaminda@admin.com',
        'role_type' => 'Cashier',
        'password' => Hash::make('naturalCashier@chaminda'),
    ]);
}

if (!\App\Models\User::where('email', 'sasees@admin.com')->exists()) {
    \App\Models\User::factory()->create([
        'name' => 'Cashiersasees',
        'email' => 'sasees@admin.com',
        'role_type' => 'Cashier',
        'password' => Hash::make('naturalCashier@sasees'),
    ]);
}



    }
}
