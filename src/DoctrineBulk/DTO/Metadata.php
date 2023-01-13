<?php

declare(strict_types=1);

namespace DoctrineBulk\DTO;

use DoctrineBulk\Generator\BulkGeneratorInterface;

/**
 * Class MetadataDto
 */
final class Metadata
{
    /**
     * @var ColumnMetadataInterface[]
     */
    private array $fields = [];

    /**
     * @var array<int, string>
     */
    private array $idFields = [];

    private ?BulkGeneratorInterface $generator = null;

    /**
     * @var array<string, string[]>
     */
    private array $lifeCycleCallBacks;

    public function __construct(private ?string $table = null)
    {
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function addField(string $field, ColumnMetadataInterface $column): void
    {
        $this->fields[$field] = $column;
    }

    /**
     * @return ColumnMetadataInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Is table has field?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * Get field by it's name. Risky!
     *
     * @param string $name
     *
     * @return ColumnMetadataInterface
     */
    public function getField(string $name): ColumnMetadataInterface
    {
        return $this->fields[$name];
    }

    /**
     * @return array<int, string>
     */
    public function getIdFields(): array
    {
        return $this->idFields;
    }

    /**
     * @param  array<int, string> $idFields
     * @return void
     */
    public function setIdFields(array $idFields): void
    {
        $this->idFields = $idFields;
    }

    public function getGenerator(): ?BulkGeneratorInterface
    {
        return $this->generator;
    }

    public function setGenerator(?BulkGeneratorInterface $generator): void
    {
        $this->generator = $generator;
    }

    /**
     * @return array<string, string[]>
     */
    public function getLifeCycleCallBacks(): array
    {
        return $this->lifeCycleCallBacks;
    }

    /**
     * @param array<string, string[]> $lifeCycleCallBacks
     */
    public function setLifeCycleCallBacks(array $lifeCycleCallBacks): void
    {
        $this->lifeCycleCallBacks = $lifeCycleCallBacks;
    }
}
