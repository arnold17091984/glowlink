<?php

declare(strict_types=1);

namespace App\Enums;

enum MessagingTypeEnum: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
}
