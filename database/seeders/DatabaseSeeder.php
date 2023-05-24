<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\DrxAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $DrxAccount = DrxAccount::FirstOrCreate(["name" => "AdminCompany"], ["drx_login" => "lygun", "drx_password" => "31185"]);
        User::where(["name" => "lygun"])->update(["drx_account_id" => $DrxAccount->id]);
    }
}
