<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string)$this->id,
            'attributes'        =>[
                'title'             => $this->title,
                'description'       => $this->description,
                'date'              => $this->date?->toDateString(),
                'image_path'        => $this->image_path,
                'image_alt'         => $this->image_alt,
                'category'         => $this->category?->name, // Eloquent uses the foreign key automatically
                'tier' => $this->tier ? [
                        'id'   => $this->tier->id,
                        'name' => $this->tier->name,
                    ] : null,
                'visibility'        => $this->visibility,
                'emotions'          => EmotionResource::collection($this->whenLoaded('emotions')),
                ],
                'relationships' => [
                    'user'      =>   [
                        'id'         => $this->user?->id,
                        'user name'  => $this->user?->name,
                        // This resource is also
                        // used by Api\MeadowController, and GET /api/blooming-meadow is
                        // registered with NO authentication middleware
                        // need to protect email in public endpoints
                        'email' => $this->when(
                            $request->user()?->id === $this->user_id || (bool) $request->user()?->isAdmin(), fn () => $this->user->email
                        ),
                    ],
                ],
        ];
    }
}
