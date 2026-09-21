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

    public function register() {
        return response()->json(
            'this is my register response!');
    }

    public function logout() {
        return response()->json(
            'this is my logout repsonse'
        );
    }


}
