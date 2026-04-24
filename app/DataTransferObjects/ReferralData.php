<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class ReferralData
{
    public function __construct(
        public readonly string $user_id,
        public readonly string $name,
        public readonly ?string $profile_url,
        public readonly string $referred_by,
        public readonly string $referral_name,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'],
            name: $data['name'],
            profile_url: $data['profile_url'],
            referred_by: $data['referred_by'],
            referral_name: $data['referral_name'],
        );
    }
}
