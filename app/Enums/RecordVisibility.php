<?php

namespace App\Enums;

enum RecordVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    // NO scope resolution, ma OGGETTO SINGLETON, ne puo esistere uno solo per tutta al esecuzione del programma
    public function label() {
        return match($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
        };
    }
}
