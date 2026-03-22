<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Clinic\Reservation\Models\Reservation;

class ReservationAcceptanceNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Reservation $reservation,
        public string $acceptance
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $reservation = $this->reservation;
        $clinicName = $reservation->clinic->name ?? __('Clinic');
        $date = (string) ($reservation->date ?? '');
        $time = (string) ($reservation->slot ?? $reservation->time ?? '');

        $when = $date;
        if ($time !== '') {
            $when .= ' '.$time;
        }

        if ($this->acceptance === 'approved') {
            $title = __('Appointment approved');
            $message = __('Your appointment at :clinic on :when has been approved.', [
                'clinic' => $clinicName,
                'when' => trim($when) ?: __('the scheduled time'),
            ]);
        } else {
            $title = __('Appointment not approved');
            $message = __('Your appointment at :clinic on :when was not approved. You can contact the clinic for more information.', [
                'clinic' => $clinicName,
                'when' => trim($when) ?: __('the scheduled time'),
            ]);
        }

        $path = '/patient/appointments';

        return [
            'title' => $title,
            'message' => $message,
            'body' => $message,
            'module' => 'reservations',
            'event' => $this->acceptance === 'approved' ? 'reservation_approved' : 'reservation_not_approved',
            'priority' => 'high',
            'icon' => 'fas fa-calendar-check',
            'url' => $path,
            'action_url' => $path,
            'reservation_id' => $reservation->id,
            'clinic_id' => $reservation->clinic_id,
            'acceptance' => $this->acceptance,
        ];
    }
}
