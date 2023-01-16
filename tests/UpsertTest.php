<?php

declare(strict_types=1);

namespace Tests;

use DateTime;
use DoctrineBulk\Bulk\BulkUpsert;
use DoctrineBulk\Exceptions\FieldNotFoundException;
use DoctrineBulk\Exceptions\NullValueException;
use ReflectionClass;
use ReflectionException;
use Tests\Entity\Author;
use Tests\Entity\Book;
use Tests\Entity\Magazine;

class UpsertTest extends AbstractBulkTest
{
    /**
     * Test adding entity to bulk.
     */
    public function testEntity(): void
    {
        $manager = $this->getManager();

        $author = new Author();
        $author->setId('jnweifohg0934hgh');
        $author->setFullName('full namez');
        $author->setOtherData('random stuff');
        $book = new Book();
        $book->setAuthor($author);
        $book->setTitle('random_text');

        $bulk = new BulkUpsert($manager, Author::class);

        $author2 = new Author();
        $author2->setFullName('full namez');
        $author2->setOtherData('random stuff');
        $bulk->addEntity($author2, false);

        self::assertEquals(
            [
                ['id' => 'akwkorfmq0w0kg8scsgsos4c0',
                    'fullName' => 'full namez',
                    'otherData' => 'random stuff'
                ]
            ],
            $this->extractField($bulk, 'values')
        );

        $bulk = new BulkUpsert($manager, Book::class);
        $bulk->addEntity($book, false);

        self::assertEquals(
            [
                [
                    'id' => null,
                    'title' => 'random_text',
                    'author' => 'jnweifohg0934hgh'
                ]
            ],
            $this->extractField($bulk, 'values')
        );
    }

    /**
     * Test adding entity to bulk.
     */
    public function testEntityCompoundKey(): void
    {
        $manager = $this->getManager();

        $author = new Author();
        $author->setId('jnweifohg0934hgh');
        $author->setFullName('full namez');
        $author->setOtherData('random stuff');

        $magazine = new Magazine();
        $magazine->setYear(2022);
        $magazine->setMonth(10);
        $magazine->setAuthor($author);
        $magazine->setTitle('random_text');

        $bulk = new BulkUpsert($manager, Author::class);
        $author2 = new Author();
        $author2->setFullName('full namez');
        $author2->setOtherData('random stuff');

        $bulk->addEntity($author2, false);
        self::assertEquals(
            [
                [
                    'id' => 'akwkorfmq0w0kg8scsgsos4c0',
                    'fullName' => 'full namez',
                    'otherData' => 'random stuff'
                ]
            ],
            $this->extractField($bulk, 'values')
        );

        $bulk = new BulkUpsert($manager, Magazine::class);

        $bulk->addEntity($magazine, false);

        self::assertEquals(
            [
                [
                    'year' => 2022,
                    'month' => 10,
                    'title' => 'random_text',
                    'author' => 'jnweifohg0934hgh',
                    'createdAt' => '2022-10-17T00:00:00+00:00',
                    'updatedAt' => null,
                ]
            ],
            $this->extractField($bulk, 'values')
        );
    }

    /**
     * Test adding to array.
     */
    public function testArray(): void
    {
        $manager = $this->getManager();

        $data = ['fullName' => 'full namez', 'otherData' => 'random stuff'];

        $bulk = new BulkUpsert($manager, Author::class);
        $bulk->addValue($data);

        self::assertEquals([$data], $this->extractField($bulk, 'values'));
    }

    /**
     * Test for adding null to not nullable values.
     */
    public function testWrong(): void
    {
        $this->expectException(NullValueException::class);

        $bulk = new BulkUpsert($this->getManager(), Author::class);
        $bulk->addValue(['otherData' => '']);
    }

    /**
     * Test for adding not null values.
     */
    public function testNotExists(): void
    {
        $this->expectException(FieldNotFoundException::class);

        $bulk = new BulkUpsert($this->getManager(), Author::class);
        $bulk->addValue(['dno' => '']);
    }

    /**
     * Return value of property.
     *
     * @param object $class
     * @param string $name
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function extractField(object $class, string $name): mixed
    {
        $reflectionClass = new ReflectionClass($class);
        $property = $reflectionClass->getProperty($name);
        $property->setAccessible(true);

        $propertyValue = $property->getValue($class);

        if (is_array($propertyValue)) {
            foreach ($propertyValue as $outerKey => $otherValue) {
                foreach ($otherValue as $key => $value) {
                    if ($value instanceof DateTime) {
                        $propertyValue[$outerKey][$key] = $value->format('c');
                    }
                }
            }
        }


        return $propertyValue;
    }
}
