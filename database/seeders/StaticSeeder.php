<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaticSeeder extends Seeder
{
    public function run(): void
    {
        Payment::truncate();
        Waste::truncate();
        Household::truncate();
        User::truncate();

        // Users
        User::create([
            'name' => 'Admin',
            'email' => 'admin@waste.com',
            'password' => Hash::make('password'),
        ]);

        // Households
        $h1 = Household::create(['owner_name' => 'Joko Santoso', 'address' => 'Jl. Mawar No. 5', 'block' => 'A', 'no' => '1']);
        $h2 = Household::create(['owner_name' => 'Siti Rahayu', 'address' => 'Jl. Melati No. 12', 'block' => 'A', 'no' => '2']);
        $h3 = Household::create(['owner_name' => 'Budi Hartono', 'address' => 'Jl. Anggrek No. 8', 'block' => 'B', 'no' => '5']);
        $h4 = Household::create(['owner_name' => 'Dewi Lestari', 'address' => 'Jl. Kenanga No. 3', 'block' => 'C', 'no' => '10']);

        // H1 — clean history, all payments paid → can create new pickup
        // W1: WasteOrganic, completed, pickup_date=2026-01-12
        WasteOrganic::create([
            'household_id' => $h1->id,
            'status' => WasteStatus::Completed,
            'pickup_date' => '2026-01-12',
        ]);
        // P1: paid, 50000, payment_date=2026-01-13
        Payment::create([
            'household_id' => $h1->id,
            'amount' => 50000,
            'status' => PaymentStatus::Paid,
            'payment_date' => '2026-01-13',
        ]);
        // W2: WastePlastic, scheduled, pickup_date=2026-03-01 (no payment; not completed yet)
        WastePlastic::create([
            'household_id' => $h1->id,
            'status' => WasteStatus::Scheduled,
            'pickup_date' => '2026-03-01',
        ]);

        // H2 — pending payment → blocked from new pickups
        // W3: WastePaper, completed, pickup_date=2026-01-20
        WastePaper::create([
            'household_id' => $h2->id,
            'status' => WasteStatus::Completed,
            'pickup_date' => '2026-01-20',
        ]);
        // P2: pending, 50000 (awaiting confirmation)
        Payment::create([
            'household_id' => $h2->id,
            'amount' => 50000,
            'status' => PaymentStatus::Pending,
        ]);
        // W4: WasteElectronic, canceled, safety_check=true, pickup_date=2026-01-22 (no payment)
        WasteElectronic::create([
            'household_id' => $h2->id,
            'status' => WasteStatus::Canceled,
            'safety_check' => true,
            'pickup_date' => '2026-01-22',
        ]);

        // H3 — mix of statuses, active
        // W5: WasteElectronic, completed, pickup_date=2026-01-05, safety_check=false
        WasteElectronic::create([
            'household_id' => $h3->id,
            'status' => WasteStatus::Completed,
            'pickup_date' => '2026-01-05',
            'safety_check' => false,
        ]);
        // P3: paid, 100000, payment_date=2026-01-07
        Payment::create([
            'household_id' => $h3->id,
            'amount' => 100000,
            'status' => PaymentStatus::Paid,
            'payment_date' => '2026-01-07',
        ]);
        // W6: WasteOrganic, canceled, pickup_date=2026-01-15 (no payment)
        WasteOrganic::create([
            'household_id' => $h3->id,
            'status' => WasteStatus::Canceled,
            'pickup_date' => '2026-01-15',
        ]);
        // W7: WastePlastic, pending, pickup_date=null (all payments paid → allowed)
        WastePlastic::create([
            'household_id' => $h3->id,
            'status' => WasteStatus::Pending,
            'pickup_date' => null,
        ]);

        // H4 — failed payment → blocked from new pickups
        // W8: WasteElectronic, completed, pickup_date=2026-01-25, safety_check=true
        WasteElectronic::create([
            'household_id' => $h4->id,
            'status' => WasteStatus::Completed,
            'pickup_date' => '2026-01-25',
            'safety_check' => true,
        ]);
        // P4: failed, 100000, payment_date=2026-01-27
        Payment::create([
            'household_id' => $h4->id,
            'amount' => 100000,
            'status' => PaymentStatus::Failed,
            'payment_date' => '2026-01-27',
        ]);
    }
}
