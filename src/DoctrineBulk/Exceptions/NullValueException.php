<?php

declare(strict_types=1);

namespace DoctrineBulk\Exceptions;

final class NullValueException extends DoctrineBulkBaseException
{
    public function __construct(string $name, string $class)
    {
        parent::__construct(sprintf('Null does not allow in field "%s" of "%s"', $name, $class));
    }
}
