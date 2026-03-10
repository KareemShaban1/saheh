<?php

namespace App\Notifications;

use Modules\Clinic\Reservation\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MakeAppointmentNotification extends Notification
{
    use Queueable;

    private $reservation;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Reservation $reservation)
    {
        //
        $this->reservation = $reservation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    // configurations for database channel
    public function toDatabase($notifiable)
    {
        $reservation = $this->reservation;
        $patientName = $reservation->patient->name ?? 'Patient';
        $doctorName = optional(optional($reservation->doctor)->user)->name;
        $reservationDate = $reservation->date ?? null;
        $reservationTime = $reservation->time ?? ($reservation->slot ?? null);
        $message = "New appointment requested by {$patientName}";
        if ($doctorName) {
            $message .= " with {$doctorName}";
        }
        if ($reservationDate) {
            $message .= " on {$reservationDate}";
        }
        if ($reservationTime) {
            $message .= " at {$reservationTime}";
        }

        $actionUrl = '/clinic-dashboard/reservations/' . $reservation->id;

        return [
            'title' => 'New appointment request',
            'message' => $message,
            'body' => $message,
            'module' => 'reservations',
            'event' => 'appointment_created',
            'priority' => 'high',
            'icon' => 'fas fa-file',
            'url' => $actionUrl,
            'action_url' => $actionUrl,
            'reservation_id' => $reservation->id,
            'clinic_id' => $reservation->clinic_id,

        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $reservation = $this->reservation;
        $patientName = $reservation->patient->name ?? 'Patient';
        $doctorName = optional(optional($reservation->doctor)->user)->name;
        $reservationDate = $reservation->date ?? null;
        $reservationTime = $reservation->time ?? ($reservation->slot ?? null);
        $message = "New appointment requested by {$patientName}";
        if ($doctorName) {
            $message .= " with {$doctorName}";
        }
        if ($reservationDate) {
            $message .= " on {$reservationDate}";
        }
        if ($reservationTime) {
            $message .= " at {$reservationTime}";
        }

        return [
            'title' => 'New appointment request',
            'message' => $message,
            'body' => $message,
            'module' => 'reservations',
            'event' => 'appointment_created',
            'priority' => 'high',
            'icon' => 'fas fa-file',
            'url' => '/clinic-dashboard/reservations/' . $reservation->id,
            'action_url' => '/clinic-dashboard/reservations/' . $reservation->id,
            'reservation_id' => $reservation->id,
            'clinic_id' => $reservation->clinic_id,

        ];
    }
}
