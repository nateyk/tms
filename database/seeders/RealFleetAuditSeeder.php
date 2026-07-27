<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RealFleetAuditSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuditedFleetA14766Seeder::class,
            AuditedFleetA17808Seeder::class,
            AuditedFleetA14761Seeder::class,
            AuditedFleetA14763Seeder::class,
        ]);
    }
}
