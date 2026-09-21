<?php

namespace App\Http\Controllers;

use App\Traits\HttpResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // we use the trait
    use HttpResponse;

    public function login() {
        return 'we are in the login!';
    }
}
