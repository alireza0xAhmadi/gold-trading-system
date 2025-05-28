<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['name' => 'احمد', 'email' => 'ahmad@example.com', 'gold_balance' => 5.250, 'rial_balance' => 50000000],
            ['name' => 'رضا', 'email' => 'reza@example.com', 'gold_balance' => 3.120, 'rial_balance' => 80000000],
            ['name' => 'اکبر', 'email' => 'akbar@example.com', 'gold_balance' => 8.517, 'rial_balance' => 30000000],
            ['name' => 'فاطمه', 'email' => 'fateme@example.com', 'gold_balance' => 2.890, 'rial_balance' => 60000000],
        ];

        foreach ($users as $user) {
            User::query()->create($user);
        }
    }
}

