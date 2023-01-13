<?php
declare(strict_types = 1);

namespace DoctrineBulk\Exceptions;

final class NoDefaultValueException extends DoctrineBulkBaseException
{
    public function __construct(string $name, string $class)
    {
        parent::__construct(sprintf('No default value for field "%s" of "%s"', $name, $class));
    }
}
