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
                'date'              => $this->date,
                'image_path'        => $this->image_path,
                'image_alt'         => $this->image_alt,
                'category '         => $this->category?->name, // Eloquent uses the foreign key automatically
                'tier'              => $this->tier?->name,
                'visibility'        => $this->visibility,
                'emotions'          => $this->whenLoaded('emotions', function () {
                    return $this->emotions->pluck('name');
                }),
                ],
                'relationships' => [
                    'user'      =>   [
                        'id'         => (string)$this->user?->id,
                        'user name'  => $this->user?->name,
                        'user email' => $this->user?->email,
                    ],
                ],
        ];
    }
}
