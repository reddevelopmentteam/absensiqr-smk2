<?php

namespace App\Enums;

enum BiometricStatus: string
{
    case Pending = 'pending';
    case Eligible = 'eligible';
    case NeedsReview = 'needs_review';
    case Rejected = 'rejected';
    case Enrolled = 'enrolled';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Eligible => 'Layak',
            self::NeedsReview => 'Perlu Ditinjau',
            self::Rejected => 'Ditolak',
            self::Enrolled => 'Terdaftar',
            self::Suspended => 'Ditangguhkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-gray-100 text-gray-600',
            self::Eligible => 'bg-blue-100 text-blue-600',
            self::NeedsReview => 'bg-amber-100 text-amber-600',
            self::Rejected => 'bg-rose-100 text-rose-600',
            self::Enrolled => 'bg-green-100 text-green-600',
            self::Suspended => 'bg-orange-100 text-orange-600',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
