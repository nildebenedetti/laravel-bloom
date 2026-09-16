<?php

namespace App\Enums;

enum RecordVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public function label() {
        return match($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
        };
    }
}
