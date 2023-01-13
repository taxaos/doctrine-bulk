<?php

declare(strict_types=1);

namespace DoctrineBulk\Exceptions;

final class WrongEntityException extends DoctrineBulkBaseException
{
    public function __construct(string $excepted, object $actual)
    {
        parent::__construct(
            sprintf(
                'Bulk class created for "%s", but "%s" added.',
                $excepted,
                get_class($actual)
            )
        );
    }
}
