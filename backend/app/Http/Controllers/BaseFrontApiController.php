<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiHelperTrait;
use App\Traits\EnsureAuthTrait;
class BaseFrontApiController extends Controller
{
    //
	use ApiHelperTrait;
	use EnsureAuthTrait;
}
