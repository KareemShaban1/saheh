<?php

namespace Modules\Clinic\User\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use Modules\Clinic\User\Models\UserDoctor;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use SoftDeletes;
    use Billable;

    /**
     * Spatie roles/permissions in this app are under "web" guard.
     * Keep explicit to avoid API guard mismatch (organization_api).
     *
     * @var string
     */
    protected $guard_name = 'web';



    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'organization_id',
        'organization_type',
        'job_title'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];


    protected static function booted()
    {
        // static::addGlobalScope(new OrganizationScope);

    }

    // public function clinic()
    // {
    //     return $this->belongsTo(
    //         Clinic::class,
    //         'clinic_id',
    //         'id',
    //     );
    // }

    public function organization()
    {
        return $this->morphTo();
    }

    public function scopeFromSameOrganization($query)
    {
        $user = auth()->user();

        return $query->where('organization_type', $user->organization_type)
            ->where('organization_id', $user->organization_id);
    }

    public function userDoctors()
    {
        return $this->hasMany(UserDoctor::class);
    }
}
