<?php
declare(strict_types = 1);

namespace DoctrineBulk\Exceptions;

/**
 * Class FieldNotFoundException
 */
final class FieldNotFoundException extends DoctrineBulkBaseException
{
    public function __construct(string $entity, string $field)
    {
        parent::__construct(sprintf('Field "%s" not found in "%s"!', $field, $entity));
    }
}
