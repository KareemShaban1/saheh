<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait EnsureAuthTrait
{
    public function ensureClinicAuth(): void
    {
        if (!request()->user()) {
            abort(401, 'Unauthenticated');
        }
    }

}