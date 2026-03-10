<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Modules\Clinic\ReservationNumber\Models\ReservationNumber;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use App\Models\Scopes\OrganizationScope;
use App\Models\Settings;

class ProcessDailyReservations extends Command
{
    protected $signature = 'reservations:process-daily';
    protected $description = 'Check and create daily reservation entries based on settings';

    public function handle()
    {
        $date = Carbon::today()->toDateString();

        $clinics = Clinic::all();
        foreach ($clinics as $clinic) {
            $settings = Settings::where('type', 'clinic_reservations_settings')
                ->where('organization_id', $clinic->id)
                ->pluck('value', 'key')
                ->toArray();


            if (empty($settings)) {
                $this->error('Reservation settings not found.');
                \Log::error('Reservation settings not found.');
                return;
            }

            if (($settings['reservation_settings'] ?? null) === 'number') {
                if (!ReservationNumber::where('reservation_date', $date)->exists()) {
                    ReservationNumber::create([
                        'reservation_date' => $date,
                        'num_of_reservations' => $settings['reservation_numbers_default'] ?? 0,
                        'clinic_id' => $clinic->id,
                    ]);

                    $this->info("ReservationNumber added for $date");
                    \Log::info("ReservationNumber added for $date");
                } else {
                    $this->info("ReservationNumber already exists for $date");
                }
            } elseif (($settings['reservation_settings'] ?? null) === 'slots') {
                if (!ReservationSlots::where('date', $date)->exists()) {
                    ReservationSlots::create([
                        'date' => $date,
                        'start_time' => $settings['reservation_slots_start_time'] ?? '09:00:00',
                        'end_time' => $settings['reservation_slots_end_time'] ?? '17:00:00',
                        'duration' => $settings['reservation_slots_duration'] ?? 30,
                        'clinic_id' => $clinic->id,
                    ]);

                    $this->info("ReservationSlot added for $date");
                    \Log::info("ReservationSlot added for $date");
                } else {
                    $this->info("ReservationSlots already exists for $date");
                }
            } else {
                $this->warn('Invalid reservation_settings value: ' . ($settings['reservation_settings'] ?? 'null'));
                \Log::warning('Invalid reservation_settings value.', $settings);
            }
        }
    }
}