<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Traits\WhatsAppNotificationTrait;

class ClinicActivationNotification extends Notification implements ShouldQueue
{
    use Queueable, WhatsAppNotificationTrait;

    protected $clinic;
    protected $user;
    protected $activationToken;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($clinic, $user, $activationToken)
    {
        $this->clinic = $clinic;
        $this->user = $user;
        $this->activationToken = $activationToken;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $activationUrl = url("/activate-clinic/{$this->activationToken}");

        return (new MailMessage)
            ->subject('Activate Your Clinic Account')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('Welcome to ' . $this->clinic->name . '!')
            ->line('Your clinic registration has been completed successfully.')
            ->line('To start using your clinic account, please activate it by clicking the button below:')
            ->action('Activate Clinic', $activationUrl)
            ->line('This activation link will expire in 24 hours.')
            ->line('If you did not register for this clinic, please ignore this email.')
            ->salutation('Best regards, ' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'clinic_id' => $this->clinic->id,
            'user_id' => $this->user->id,
            'activation_token' => $this->activationToken,
        ];
    }

    /**
     * Send WhatsApp notification for clinic activation
     */
    public function sendWhatsAppNotification()
    {
        if (!$this->isWhatsAppEnabled()) {
            return false;
        }

        $activationUrl = url("/activate-clinic/{$this->activationToken}");

        $message = "🏥 Welcome to {$this->clinic->name}!\n\n";
        $message .= "Dear {$this->user->name},\n\n";
        $message .= "Your clinic registration has been completed successfully.\n\n";
        $message .= "To activate your clinic account, please click the link below:\n";
        $message .= "🔗 {$activationUrl}\n\n";
        $message .= "This activation link will expire in 24 hours.\n\n";
        $message .= "Best regards,\n" . config('app.name') . " Team";

        return $this->sendCustomWhatsAppNotification($this->clinic->phone, $message);
    }
}
