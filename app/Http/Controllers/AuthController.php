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
        // check for credentials meeting requirements
        $request->validated($request->all());

        // if so, check if they are authorized
        // if the authentication attempt fails
        If (!Auth::attempt([ 
            'email' => $request->email, 
            'password' => $request->password])) {
            // respond with error (clearly NO DATA)
            return $this->error('', 'credentials do not match', 401); // code for unauthorized
            }

        // IF AUTHORIZED

        // assign first user matching the email (as it is unique value)
        $user = User::where('email', $request->email)->first();

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('Api Token of' . $user->name)->plainTextToken
        ]);


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
            // let's delete the access token
            // no worries, a new one will be created at login
            Auth::user()->currentAccessToken()->delete();

            return $this->success([
                'message' => 'You have successfully been logged out. Come back soon!'
            ]);

    }


}
