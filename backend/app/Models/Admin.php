<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Modules\Clinic\User\Models\User;
class Admin extends User
{
    use HasFactory;
}