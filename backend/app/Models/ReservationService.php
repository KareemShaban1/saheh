<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\Reservation\Models\Reservation;

class ReservationService extends Model
{
    use HasFactory;

    protected $table = 'reservation_services';

    protected $fillable = [
        'reservation_id',
        'service_id',
        'fee',
        'notes'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function Service()
    {
        return $this->belongsTo(Service::class);
    }
}