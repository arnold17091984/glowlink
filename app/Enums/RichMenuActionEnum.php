<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RichMenuActionEnum: string implements HasLabel
{
    case LINK = 'link';
    case MESSAGE = 'message';
    case PHONE = 'phone';
    case MAIL = 'mail';
    case SHARE_OA = 'share_oa';
    case SHARE_MESSAGE = 'share_message';
    case SUB_MENU = 'sub_menu';
    case AUTO_RESPONSE = 'auto_response';
    case NO_ACTION = 'no_action';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LINK => 'リンク (URL)',
            self::MESSAGE => 'メッセージ送信',
            self::PHONE => '電話発信',
            self::MAIL => 'メール送信',
            self::SHARE_OA => '友だちに公式アカウントを紹介',
            self::SHARE_MESSAGE => '友だちにメッセージを共有',
            self::SUB_MENU => 'サブメニューに切替',
            self::AUTO_RESPONSE => '自動応答に紐づけ',
            self::NO_ACTION => '何もしない',
        };
    }
}
