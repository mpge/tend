<?php

namespace App\Enums;

enum TaskBucket: string
{
    case Important = 'important';
    case Eventual = 'eventual';

    public function label(): string
    {
        return match ($this) {
            self::Important => 'Important',
            self::Eventual => 'Eventual',
        };
    }
}
