MetaModels database objects for phpunit
=======================================

What you can expect here.
-------------------------

This repository holds a small subset of classes that can be used to fake a Contao database connection.

To do so, you define the queries and their result sets.

Installation
------------

Add `"metamodels/phpunit-contao-database": "~1.0"` to your `composer.json` in the `require-dev` section and you are set.

How to use it.
--------------

In your unit test you simply replace the original Contao database classes by calling the following code (before the 
original Contao database classes get loaded via autoloading!). A good place might be the `setUp()`-method of your test
case.

```php
class MyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Register the database.
     *
     * @return void
     */
    public function setUp()
    {
        \MetaModels\Test\Contao\Database::register();
    }
}
```

Using it in unit tests
----------------------

You should use dependency injection for proper unit testing, then you can use something like this:

```php
$objectToTest = new ClassToTest();
$database     = \MetaModels\Test\Contao\Database::getNewTestInstance();

// Inject the database into the instance.
$objectToTest->setDatabase($database);

$database
    ->getQueryCollection()
    ->theQuery('SELECT * FROM test WHERE id=?')
    ->with(1)
    ->result()
        ->addRow(
            array(
              'id'     => 1,
              'tstamp' => 343094400,
              'title'  => 'test'
            ),
        );

// This method will call the above query internally and will receive the given result.
$result = $objectToTest->testMethod();

$this->assertEquals(1, $result->getId());
$this->assertEquals(343094400, $result->getModificationTime());
$this->assertEquals('test', $result->getTitle());
```

If you should be in the unfortunate position that you can not use dependency injection, as the underlying code is using
```Database::getInstance()```, you are not out of luck. You can use the method 
```$database = \MetaModels\Test\Contao\Database::getNewTestInstance();``` which will return the "default" database 
instance then.

However using this approach is not suggested as the instance will be shared over all unit tests.
