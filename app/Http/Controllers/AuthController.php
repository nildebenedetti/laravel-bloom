<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreUserRequest;
use App\Models\User;
use App\Traits\HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // we use the trait
    use HttpResponse;

    public function login() {
        return 'we are in the login!';
    }

    public function register(StoreUserRequest $request) { //pass form request as param
        // if validation is passed
        $request->validated($request->all());

        // I'LL CREATE A USER
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // if successful, we use Http response trait to pass repsonse
        // with entry parameter
        return $this->success([
            'user' => $user,
            'token' => $user->createToken('API Token of' . $user->name)->plainTextToken,
        ]);

    }

    public function logout() {
        return response()->json(
            'this is my logout repsonse'
        );
    }


}
