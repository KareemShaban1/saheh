<?php

namespace App\Notifications;

use App\Models\TempData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationRegistredNotification extends Notification
{
    use Queueable;

    private $tempData;

    /**
     * Create a new notification instance.
     */
    public function __construct(TempData $tempData)
    {
        //
        $this->tempData = $tempData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toDatabase($notifiable)
    {
        $tempData = $this->tempData;
    
        $organizationData = json_decode($tempData->data ?? '{}', true);
        $organizationType = '';
        $url = '';
    
        switch ($tempData->type) {
            case 'clinic':
                $organizationType = 'Clinic';
                $url = url('/admin/clinic-temp-data/pendingClinics');
                break;
            case 'medicalLaboratory':
                $organizationType = 'Medical Laboratory';
                $url = url('/admin/medical-laboratories-temp-data/pendingMedicalLaboratories');
                break;
            case 'radiologyCenter':
                $organizationType = 'Radiology Center';
                $url = url('/admin/radiology-centers-temp-data/pendingRadiologyCenters');
                break;
            default:
                $organizationType = 'Organization';
                $url = url('/admin/dashboard');
                break;
        }
    
        return [
            'body' => "New {$organizationType} with name {$organizationData['name']} registration submitted.",
            'icon' => 'fas fa-file',
            'url' => $url,
        ];
    }
    
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
