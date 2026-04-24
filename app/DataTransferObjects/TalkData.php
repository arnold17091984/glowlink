<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\FlagEnum;
use App\Models\User;

class TalkData
{
    public function __construct(
        public readonly ?int $sender_id,
        public readonly string $sender_type,
        public readonly ?int $receiver_id,
        public readonly string $receiver_type,
        public readonly array $message,
        public readonly FlagEnum $flag,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $flag = $data['sender_type'] === User::class ? FlagEnum::ADMIN : FlagEnum::UNRESOLVED;

        return new self(
            sender_id: (int) $data['sender_id'],
            sender_type: $data['sender_type'],
            receiver_id: (int) $data['receiver_id'],
            receiver_type: $data['receiver_type'],
            message: $data['message'],
            flag: $flag,
        );
    }
}
