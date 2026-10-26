<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\Api\StoreUserRequest;
use App\Models\User;
use App\Traits\HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // we use the trait
    use HttpResponse;

    public function login(LoginUserRequest $request) {

        $request->validated($request->all());

        If (!Auth::attempt([ 
            'email' => $request->email, 
            'password' => $request->password])) {
            
            return $this->error('', 'credentials do not match', 401);
            }

        // IF AUTHORIZED

        $user = User::where('email', $request->email)->first();

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('Api Token of' . $user->name)->plainTextToken
        ]);


    }

    public function register(StoreUserRequest $request) { // class injection of form request

        $request->validated($request->all());

        // static method create = instatiation + create in DB
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
            // let's delete the access token
            // no worries, a new one will be created at login
            Auth::user()->currentAccessToken()->delete();

            return $this->success([
                'message' => 'You have successfully been logged out. Come back soon!'
            ]);

    }


}
