<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserProfileTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DEFAULT ADMIN
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@bloom.org',
            'password' => 'safepsw@bloom2026'

        ]);

        $admin->profile()->create([
            'bio' => 'System Administrator, Master of Puppets and cat lover.'
        ]);

        // standard user
        $user = User::create([
            'name' => 'Ophelia',
            'email' => 'offHell@live.com',
            'role' => 'user',
        ]);

        $user->profile()->create([ 'bio' => 'Hopeless Romantic. Love Flowers and being around kind souls. Currently healing my broken heart.' ]);
    }

}