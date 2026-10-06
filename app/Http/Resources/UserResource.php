<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Declared here (not on JsonResource) so only this resource opts out of
     * Laravel's default `data` wrapper: GET /api/user has always returned the
     * bare user object and the SPA reads it unwrapped.
     */
    public static $wrap;

    /**
     * Transform the resource into an array.
     *
     * Returned only by GET /api/user. The endpoint previously serialized the
     * raw User model, so the keys stay flat and unchanged — `bio` is the one
     * addition, projected from the user_profiles relation.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role,
            'bio'               => $this->profile?->bio,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
