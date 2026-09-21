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
            'attributes' =>[
                'title' => $this->title,
                'date' => $this->date,
                'image_path' => $this->image_path,
                'image_alt' => $this->image_alt,
                'category ' => $this->category?->name,
                'tier' => $this->tier?->name,
                'visibility' => $this->visibility
                ],
                'relationships' => [
                    'user' => [
                        'id' => (string)$this->user?->id,
                        'user name' => $this->user?->name,
                        'user email' => $this->user?->email,
                    ],
                ],
        ];
    }
}
