<?php
declare(strict_types=1);

namespace DoctrineBulk\Bulk;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use DoctrineBulk\Exceptions\FieldNotFoundException;
use DoctrineBulk\Exceptions\NoDefaultValueException;
use DoctrineBulk\Exceptions\NullValueException;
use DoctrineBulk\Exceptions\WrongEntityException;
use InvalidArgumentException;

/**
 * Allows to upsert multiple doctrine entities to database.
 */
class BulkUpsert extends AbstractBulk
{
    public const FLAG_NONE = 0;
    public const FLAG_IGNORE_DUPLICATES = 1 << 1;
    public const FLAG_NO_RETURN_ID = 1 << 2;

    public const DEFAULT_ROWS = 1000;

    /** @var array<int, array<mixed, mixed>> */
    private array $values = [];

    /**
     * Data.
     *
     * @param array<string, mixed> $data
     *
     * @return void
     * @throws FieldNotFoundException
     * @throws NullValueException
     */
    public function addValue(array $data): void
    {
        foreach (array_keys($data) as $name) {
            if (!$this->metadata->hasField((string)$name)) {
                throw new FieldNotFoundException($this->class, $name);
            }
        }
        foreach ($this->metadata->getFields() as $field => $column) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$column->isNullable() && !array_key_exists($field, $data)) {
                throw new NullValueException($this->class, $field);
            }
        }

        $this->values[] = $data;
    }

    /**
     * Adds entity to persist queue.
     *
     * @param object $entity
     *
     * @return void
     *
     * @throws FieldNotFoundException
     * @throws NoDefaultValueException
     * @throws NullValueException
     * @throws WrongEntityException
     * @throws \DoctrineBulk\Exceptions\MissingParentClassException
     * @throws \ReflectionException
     */
    public function addEntity(object $entity): void
    {
        if (get_class($entity) !== $this->class) {
            throw new WrongEntityException($this->class, $entity);
        }

        $ret = [];

        $this->callLifeCycleCallbackFunctionsIfDefined($entity);

        foreach ($this->metadata->getFields() as $field => $column) {
            $classValue = $this->getJoinedEntityValue(
                $column,
                $this->getClassValue($this->reflection, $field, $entity),
                $field
            );

            if (!$classValue->isInitialised()) {
                if (!$column->hasDefault()) {
                    throw new NoDefaultValueException($this->class, $field);
                }

                $ret[$field] = $column->getDefault();
                continue;
            }

            $value = $classValue->getValue();
            if ($value === null && !$column->isNullable()) {
                throw new NullValueException($this->class, $field);
            }

            $ret[$field] = $value;
        }

        $generator = $this->metadata->getGenerator();
        $idFields = $this->metadata->getIdFields();

        foreach ($idFields as $idField) {
            if ($generator !== null && null === $ret[$idField]) {
                $ret[$idField] = $generator->generateBulk($this->manager, $this->class, $ret);
            }
        }

        $this->values[] = $ret;
    }

    private function isNewObject(object $entity): bool
    {
        try {
            return match ($this->manager->getUnitOfWork()->getEntityState($entity)) {
                UnitOfWork::STATE_DETACHED, UnitOfWork::STATE_REMOVED, UnitOfWork::STATE_MANAGED => false,
                UnitOfWork::STATE_NEW => true
            };
        } catch (TableNotFoundException $exception) {
            return true;
        }

    }

    /**
     * Executes insert to database and returns id of first inserted element.
     *
     * @param int $flags
     * @param int $maxRows
     *
     * @return string|int|false|null
     * @throws ConversionException
     * @throws Exception
     */
    public function execute(int $flags = self::FLAG_NONE, int $maxRows = self::DEFAULT_ROWS): string|int|false|null
    {
        if ($maxRows < 1) {
            throw new InvalidArgumentException('maxRows has to be at least 1');
        }
        if (!count($this->values)) {
            return null;
        }

        if ($flags & self::FLAG_IGNORE_DUPLICATES) {
            $temp = [];
            foreach ($this->values as $value) {
                $temp[implode('.', $value)] = $value;
            }

            $this->values = array_values($temp);
            unset($temp);
        }

        $lastId = null;
        foreach (array_chunk($this->values, $maxRows) as $values) {
            $lastInsertId = $this->executePartial($flags, $values);
            $lastId = $lastId ?? $lastInsertId;
        }
        $this->values = [];

        return $lastId;
    }

    /**
     * Executes insert to database and returns id of first inserted element.
     *
     * @param int $flags
     * @param array<int, array<mixed, mixed>> $values
     *
     * @return string|int|false|null
     * @throws ConversionException
     * @throws Exception
     */
    private function executePartial(int $flags, array $values): string|int|false|null
    {
        $fields = $this->getAllUsedFields($values);
        $dbFields = array_map(
            function (string $column) {
                return $this->escape($this->metadata->getField($column)->getName());
            },
            $fields
        );

        $onDuplicateKeyParts = [];
        foreach ($dbFields as $dbFieldName) {
            $onDuplicateKeyParts[] = sprintf('%s=VALUES(%s)', $dbFieldName, $dbFieldName);
        }

        if ($this->metadata->getTable() === null) {
            throw new InvalidArgumentException('table cannot be null');
        }
        $query = sprintf(
            'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $this->escape($this->metadata->getTable()),
            implode(
                ', ',
                $dbFields
            ),
            trim(str_repeat(sprintf('(%s), ', implode(', ', array_fill(0, count($fields), '?'))), count($values)), ', '),
            implode(', ', $onDuplicateKeyParts)
        );

        $statement = $this->manager->getConnection()->prepare($query);

        $index = 0;
        foreach ($values as $row) {
            foreach ($fields as $name) {
                $index++;
                $value = $row[$name] ?? null;
                if (
                    in_array($name, $this->metadata->getIdFields(), true) && $generate = $this->metadata->getGenerator()) {
                    $value = $generate->generateBulk($this->manager, $this->class, $row);
                }
                $this->bind($statement, $index, $this->metadata->getField($name), $value);
            }
        }

        $statement->executeQuery();

        $noLastId = ($flags & self::FLAG_NO_RETURN_ID) === self::FLAG_NO_RETURN_ID || $this->metadata->getGenerator() !== null;

        if ($noLastId) {
            return null;
        }

        return $this->manager->getConnection()->lastInsertId();
    }

    /**
     * works only for Events::prePersist and Events::preUpdate at the moment
     *
     * @param object $entity
     * @return void
     */
    private function callLifeCycleCallbackFunctionsIfDefined(object $entity): void
    {
        if ($this->metadata->getLifeCycleCallBacks()) {
            $isNewObject = $this->isNewObject($entity);
            foreach ($this->metadata->getLifeCycleCallBacks() as $callBackName => $callBackFunctions) {
                switch ($callBackName) {
                    case Events::prePersist:
                        if ($isNewObject) {
                            foreach ($callBackFunctions as $callBackFunction) {
                                $entity->$callBackFunction();
                            }
                        }
                        break;
                    case Events::preUpdate:
                        if (!$isNewObject) {
                            foreach ($callBackFunctions as $callBackFunction) {
                                $entity->$callBackFunction();
                            }
                        }
                        break;
                }
            }
        }
    }
}
