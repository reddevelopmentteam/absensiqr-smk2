<?php

namespace App\Http\Resources\Api\V1\Library;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nama_lengkap,
            'tingkat' => $this->whenLoaded('tingkat', fn () => $this->tingkat?->nama),
            'jurusan' => $this->whenLoaded('jurusan', fn () => $this->jurusan?->nama),
            'indeks' => $this->whenLoaded('indeks', fn () => $this->indeks?->nama),
        ];
    }
}
