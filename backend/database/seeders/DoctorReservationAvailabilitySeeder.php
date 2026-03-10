<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Scopes\OrganizationScope;
use App\Models\Settings;
use App\Models\Shared\Patient;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;

class DoctorReservationAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonthsNoOverflow(2);
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        $clinics = Clinic::query()->pluck('id');

        // Ensure every clinic has the minimum reservation settings needed by the API.
        $defaultSettings = [
            'reservation_settings' => 'number', // can be overridden per clinic later
            'reservation_numbers_default' => '15',
            'reservation_slots_start_time' => '12:00',
            'reservation_slots_end_time' => '17:00',
            'reservation_slots_interval' => '30',
        ];

        $settingsUpserts = [];
        foreach ($clinics as $clinicId) {
            foreach ($defaultSettings as $key => $value) {
                // Alternate reservation type per clinic to cover both flows.
                if ($key === 'reservation_settings') {
                    $value = ($clinicId % 2 === 0) ? 'slots' : 'number';
                }

                $settingsUpserts[] = [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'clinic_reservations_settings',
                    'organization_id' => $clinicId,
                    'organization_type' => Clinic::class,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Upsert by the natural key used throughout the codebase.
        DB::table('settings')->upsert(
            $settingsUpserts,
            ['organization_id', 'organization_type', 'type', 'key'],
            ['value', 'updated_at']
        );

        $settingsByClinic = Settings::withoutGlobalScope(OrganizationScope::class)
            ->where('type', 'clinic_reservations_settings')
            ->where('organization_type', Clinic::class)
            ->whereIn('organization_id', $clinics)
            ->get()
            ->groupBy('organization_id')
            ->map(fn($rows) => $rows->pluck('value', 'key'));

        $doctors = Doctor::query()->get(['id', 'clinic_id']);

        // Important: Laravel's upsert() only prevents duplicates if the table has a UNIQUE index.
        // To guarantee "one availability row per doctor per day", we wipe the seeded range then insert fresh.
        $doctorIds = $doctors->pluck('id')->values();
        if ($doctorIds->isNotEmpty()) {
            DB::table('reservation_numbers')
                ->whereIn('doctor_id', $doctorIds)
                ->whereBetween('reservation_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->delete();

            DB::table('reservation_slots')
                ->whereIn('doctor_id', $doctorIds)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->delete();
        }

        // Bulk seed availability (numbers + slots) for all doctors.
        $reservationNumbersRows = [];
        $reservationSlotsRows = [];

        foreach ($doctors as $doctor) {
            $clinicId = (int) $doctor->clinic_id;

            $clinicSettings = $settingsByClinic->get($clinicId, collect());
            $numbersDefault = (int) ($clinicSettings['reservation_numbers_default'] ?? 15);
            $startTime = (string) ($clinicSettings['reservation_slots_start_time'] ?? '12:00');
            $endTime = (string) ($clinicSettings['reservation_slots_end_time'] ?? '17:00');
            $intervalMinutes = (int) ($clinicSettings['reservation_slots_interval'] ?? 30);

            $totalSlots = $this->countSlots($startTime, $endTime, $intervalMinutes);

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                $reservationNumbersRows[] = [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctor->id,
                    'reservation_date' => $dateStr,
                    'num_of_reservations' => $numbersDefault,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $reservationSlotsRows[] = [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctor->id,
                    'date' => $dateStr,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration' => (string) $intervalMinutes,
                    'total_reservations' => (string) $totalSlots,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($reservationNumbersRows, 2000) as $chunk) {
            DB::table('reservation_numbers')->insert($chunk);
        }

        foreach (array_chunk($reservationSlotsRows, 2000) as $chunk) {
            DB::table('reservation_slots')->insert($chunk);
        }

        // Seed a few reservations for kareem@patient.com with his assigned doctors.
        $this->seedKareemReservations($settingsByClinic, $startDate);
    }

    private function seedKareemReservations($settingsByClinic, Carbon $startDate): void
    {
        $patient = Patient::query()->where('email', 'kareem@patient.com')->first();
        if (! $patient) {
            return;
        }

        $doctorIds = DB::table('patient_organization')
            ->where('patient_id', $patient->id)
            ->whereNull('deleted_at')
            ->whereNotNull('doctor_id')
            ->pluck('doctor_id')
            ->unique()
            ->values();

        if ($doctorIds->isEmpty()) {
            return;
        }

        $doctors = Doctor::query()
            ->whereIn('id', $doctorIds)
            ->get(['id', 'clinic_id']);

        $maxDoctors = 4;
        $doctors = $doctors->take($maxDoctors);

        foreach ($doctors as $index => $doctor) {
            $clinicId = (int) $doctor->clinic_id;
            $clinicSettings = $settingsByClinic->get($clinicId, collect());
            $type = (string) ($clinicSettings['reservation_settings'] ?? 'number');

            $date = $startDate->copy()->addDays(1 + ($index * 2));
            $dateStr = $date->format('Y-m-d');

            $payload = [
                'patient_id' => $patient->id,
                'clinic_id' => $clinicId,
                'doctor_id' => $doctor->id,
                'date' => $dateStr,
                'month' => $date->format('m'),
                'status' => 'waiting',
                'acceptance' => 'pending',
                'payment' => 'not_paid',
                'cost' => 0,
            ];

            if ($type === 'slots') {
                $startTime = (string) ($clinicSettings['reservation_slots_start_time'] ?? '12:00');
                $endTime = (string) ($clinicSettings['reservation_slots_end_time'] ?? '17:00');
                $intervalMinutes = (int) ($clinicSettings['reservation_slots_interval'] ?? 30);

                $slots = $this->buildSlots($startTime, $endTime, $intervalMinutes);
                $reserved = Reservation::query()
                    ->where('clinic_id', $clinicId)
                    ->where('doctor_id', $doctor->id)
                    ->where('date', $dateStr)
                    ->pluck('slot')
                    ->filter()
                    ->values()
                    ->all();

                $available = array_values(array_diff($slots, $reserved));
                if (empty($available)) {
                    continue;
                }

                $payload['slot'] = $available[0];
                $payload['reservation_number'] = null;
            } else {
                $max = (int) ($clinicSettings['reservation_numbers_default'] ?? 15);

                $reserved = Reservation::query()
                    ->where('clinic_id', $clinicId)
                    ->where('doctor_id', $doctor->id)
                    ->where('date', $dateStr)
                    ->pluck('reservation_number')
                    ->map(fn($v) => (int) $v)
                    ->filter()
                    ->values()
                    ->all();

                $all = range(1, max($max, 1));
                $available = array_values(array_diff($all, $reserved));
                if (empty($available)) {
                    continue;
                }

                $payload['reservation_number'] = (string) $available[0];
                $payload['slot'] = null;
            }

            // Ensure availability rows exist (safe when running this seeder alone).
            ReservationNumber::query()->updateOrCreate(
                [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctor->id,
                    'reservation_date' => $dateStr,
                ],
                [
                    'num_of_reservations' => (int) ($clinicSettings['reservation_numbers_default'] ?? 15),
                ]
            );

            ReservationSlots::query()->updateOrCreate(
                [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctor->id,
                    'date' => $dateStr,
                ],
                [
                    'start_time' => (string) ($clinicSettings['reservation_slots_start_time'] ?? '12:00'),
                    'end_time' => (string) ($clinicSettings['reservation_slots_end_time'] ?? '17:00'),
                    'duration' => (string) ($clinicSettings['reservation_slots_interval'] ?? '30'),
                    'total_reservations' => (string) $this->countSlots(
                        (string) ($clinicSettings['reservation_slots_start_time'] ?? '12:00'),
                        (string) ($clinicSettings['reservation_slots_end_time'] ?? '17:00'),
                        (int) ($clinicSettings['reservation_slots_interval'] ?? 30),
                    ),
                ]
            );

            Reservation::query()->firstOrCreate(
                [
                    'patient_id' => $payload['patient_id'],
                    'clinic_id' => $payload['clinic_id'],
                    'doctor_id' => $payload['doctor_id'],
                    'date' => $payload['date'],
                    'reservation_number' => $payload['reservation_number'] ?? null,
                    'slot' => $payload['slot'] ?? null,
                ],
                $payload
            );
        }
    }

    private function buildSlots(string $startTime, string $endTime, int $intervalMinutes): array
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        $duration = max($intervalMinutes, 1);

        $slots = [];
        while ($start + ($duration * 60) <= $end) {
            $slots[] = date('H:i', $start);
            $start += $duration * 60;
        }

        return $slots;
    }

    private function countSlots(string $startTime, string $endTime, int $intervalMinutes): int
    {
        return count($this->buildSlots($startTime, $endTime, $intervalMinutes));
    }
}

