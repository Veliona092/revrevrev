<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashExistingPasswordsSeeder extends Seeder
{
    public function run()
    {
        $users = DB::table('users')->select('id', 'username', 'password')->get();

        foreach ($users as $user) {
            $pwd = $user->password;

            if (empty($pwd)) {
                $this->command->info("Skipping user id {$user->id}: empty password.");

                continue;
            }

            $info = password_get_info($pwd);

            // If algo === 0 it's not a recognized hash (treat as plaintext)
            if ($info['algo'] === 0) {
                DB::table('users')->where('id', $user->id)->update([
                    'password' => Hash::make($pwd),
                    'updated_at' => Carbon::now(),
                ]);
                $this->command->info("Hashed plaintext password for user {$user->username} (id {$user->id}).");

                continue;
            }

            // Already hashed: optionally rehash if algorithm/options changed
            if (Hash::needsRehash($pwd)) {
                DB::table('users')->where('id', $user->id)->update([
                    'password' => Hash::make($pwd),
                    'updated_at' => Carbon::now(),
                ]);
                $this->command->info("Rehashed password for user {$user->username} (id {$user->id}).");
            } else {
                $this->command->info("Already hashed: user {$user->username} (id {$user->id}).");
            }
        }

        $this->command->info('Existing passwords processed.');
    }
}
