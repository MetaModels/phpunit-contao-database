<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage Tests
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Test;

use MetaModels\Test\Contao\Database;

/**
 * Abstract base class for test cases.
 */
class BootDatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set our database.
     *
     * @return void
     */
    public function testRegister()
    {
        Database::register();

        $this->assertInstanceOf('MetaModels\Test\Contao\Database', \Database::getInstance());
        $this->assertInstanceOf('Database', \Database::getInstance());
    }

    /**
     * Test that the getInstance() methods work as expected.
     *
     * @return void
     */
    public function testNewInstance()
    {
        Database::register();

        $this->assertSame(\Database::getInstance(), Database::getInstance());
        $this->assertNotSame(\Database::getInstance(), Database::getNewTestInstance());
    }

    /**
     * Test that the database can retrieve data.
     *
     * @return void
     */
    public function testRetrieve()
    {
        $database = Database::getNewTestInstance();

        $database
            ->getQueryCollection()
            ->theQuery('SELECT * FROM test WHERE id=?')
            ->with(1)
            ->result()
                ->addRow(
                    array(
                        'id'     => 1,
                        'tstamp' => 343094400,
                    )
                );

        $statement = $database->prepare('SELECT * FROM test WHERE id=?');
        $result    = $statement->execute(1);

        $this->assertInstanceOf('MetaModels\Test\Contao\Database\Statement', $statement);
        $this->assertInstanceOf('Contao\Database\Statement', $statement);
        $this->assertInstanceOf('Database\Statement', $statement);
        $this->assertInstanceOf('MetaModels\Test\Contao\Database\Result', $result);
        $this->assertInstanceOf('Contao\Database\Result', $result);
        $this->assertInstanceOf('Database\Result', $result);

        $this->assertSame(1, $result->numRows);
        $this->assertSame(
            array(
                'id'     => 1,
                'tstamp' => 343094400,
            ),
            $result->row()
        );

        $statement = $database->prepare('SELECT * FROM test WHERE id=?');
        $result    = $statement->execute(1);

        $counter = 0;
        while ($result->next()) {
            $counter++;
        }

        $this->assertEquals(1, $counter);
    }
}
