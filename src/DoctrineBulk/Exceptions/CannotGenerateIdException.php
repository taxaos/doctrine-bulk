<?php

declare(strict_types=1);

namespace DoctrineBulk\Exceptions;

/**
 * Class CannotGenerateIdException
 */
final class CannotGenerateIdException extends DoctrineBulkBaseException
{
    /**
     * CannotGenerateIdException constructor.
     *
     * @param string $entity
     * @param string $field
     */
    public function __construct(string $entity, string $field)
    {
        parent::__construct(
            sprintf(
                'Cannot generate Id for "%s" required field "%s" not exists!',
                $entity,
                $field
            )
        );
    }
}
