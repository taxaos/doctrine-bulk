<?php
declare(strict_types = 1);

namespace DoctrineBulk\DTO;

/**
 * Class JoinColumnMetadata
 */
final class JoinColumnMetadata extends AbstractColumnMetadata
{
    private string $referenced;

    /**
     * Setter for Referenced.
     *
     * @param string $referenced
     *
     * @return void
     */
    public function setReferenced(string $referenced): void
    {
        $this->referenced = $referenced;
    }

    /**
     * Getter for Referenced
     *
     * @return string
     */
    public function getReferenced(): string
    {
        return $this->referenced;
    }
}
