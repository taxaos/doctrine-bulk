<?php

declare(strict_types=1);

namespace DoctrineBulk\Generator;

final class SimpleHash
{
    private function __construct()
    {
    }

    /**
     * Generates simple hash for PRIMARY key, char(25).
     *
     * @param string[]|int[]|float[] $data
     *
     * @return string
     */
    public static function create(array $data): string
    {
        return base_convert(md5(implode('_', $data)), 16, 36);
    }
}
