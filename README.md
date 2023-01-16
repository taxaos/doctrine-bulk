# Doctrine-Bulk Classes for MySQL
Adds ability to multiple upsert / insert on duplicate (MySQL only) of entities or array to database using doctrine schema and ORM objects.

    INSERT ... ON DUPLICATE KEY UPDATE Statement for ORM objects
        INSERT INTO t1 (a,b,c) VALUES (1,2,3)
        ON DUPLICATE KEY UPDATE c=c+1;

https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html

Be warned: this function will just take your list of your ORM objects and
will create the insert on duplicate sql query

* Supported Relationship / Join Types are ONE_TO_ONE AND MANY_TO_ONE
* LifeCycleCallbacks Events::prePersist / Events::preUpdate are supported
* Your ORM objects can be detached from session to avoid the insert/update triggered by ORM..
** I opted to give you flexibility here, use $detach parameter
* Changes will be sent to DB in chunks

If the world would be nice and Doctrine able to do something else then single insert/update queries for MySQL, i would not need to do this.

Save the planet with less energy used for wasted queries :)

### Samples
#### Default usage
```php
<?php
declare(strict_types = 1);

use Doctrine\ORM\EntityManagerInterface;
use DoctrineBulk\Bulk\BulkUpsert;

/**
 * Class DbWrite
 */
class DbWrite {
    private EntityManagerInterface $manager;

    /**
     * Creates two users in one query.
     *
     * @return int
     */
    public function updateExistingUsersAndCreateTwoUsers(): int
    {
        $dbUsers = []; // imagine some loaded users from DB and some changed data from your code 

        $bulkUpsert = new BulkUpsert($this->manager, User::class);

        foreach ($dbUsers as $dbUser) {
            $bulkUpsert->addEntity($dbUser, detach: true);
        }

        // now 2 new users
        $bulkUpsert->addEntity(new User('user 1', 'password'), detach: true);
        $bulkUpsert->addEntity(new User('user 2', 'password'), detach: true);

        $firstInsertedId = (int) $bulkUpsert->execute(maxRows: 1000);

        return $firstInsertedId;
    }
}
```

Forked from https://github.com/6dreams/doctrine-bulk