<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateBioRequest;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    /**
     * Create or replace the caller's own bio.
     *
     * `user_profiles` is a hasOne, so store and update are the same
     * operation: updateOrCreate inserts the row on the first write and
     * overwrites it afterwards. The profile is always the caller's, so no id
     * (and no policy) is involved.
     */
    public function updateBio(UpdateBioRequest $request): UserResource
    {
        $user = $request->user();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['bio' => $request->validated('bio')]
        );

        return new UserResource($user->load('profile'));
    }
}
