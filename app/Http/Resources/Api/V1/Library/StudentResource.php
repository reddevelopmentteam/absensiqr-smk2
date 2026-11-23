<?php

namespace App\Http\Resources\Api\V1\Library;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isActive = $this->status === 'aktif';

        return [
            'uuid' => $this->uuid,
            'nis' => $this->nipd,
            'nipd' => $this->nipd,
            'nisn' => $this->nisn,
            'name' => $this->nama,
            'photo_url' => $this->photoUrl(),
            'birth_date' => $this->tanggal_lahir instanceof \DateTimeInterface
                ? $this->tanggal_lahir->format('Y-m-d')
                : $this->tanggal_lahir,
            'status' => $isActive ? 'active' : 'inactive',
            'is_active' => $isActive,
            'class' => [
                'id' => $this->whenLoaded('rombel', fn () => $this->rombel?->id),
                'name' => $this->whenLoaded('rombel', fn () => $this->rombel?->nama_lengkap),
            ],
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * URL absolut foto murid. Nilai `image_path` bisa berupa URL penuh
     * (hasil Storage::url) atau path relatif pada disk `public`.
     */
    private function photoUrl(): ?string
    {
        $path = trim((string) $this->image_path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        foreach (['storage/', 'public/'] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                $path = Str::after($path, $prefix);
            }
        }

        return $path === '' ? null : Storage::disk('public')->url($path);
    }
}
