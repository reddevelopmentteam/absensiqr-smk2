<?php

namespace App\Enums;

enum BiometricConsentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Revoked => 'Dicabut',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-600',
            self::Active => 'bg-green-100 text-green-600',
            self::Revoked => 'bg-rose-100 text-rose-600',
            self::Expired => 'bg-gray-100 text-gray-600',
        };
    }
}
