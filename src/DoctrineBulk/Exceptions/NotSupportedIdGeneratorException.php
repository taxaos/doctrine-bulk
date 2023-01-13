<?php
declare(strict_types=1);

namespace DoctrineBulk\Exceptions;

use DoctrineBulk\Generator\BulkGeneratorInterface;

final class NotSupportedIdGeneratorException extends DoctrineBulkBaseException
{
    public function __construct(object $name)
    {
        parent::__construct(
            sprintf(
                'To use generator "%s" in bulk please implement "%s" interface',
                get_class($name),
                BulkGeneratorInterface::class
            ));
    }
}
