<?php

/**
/**
 * This file is part of MetaModels/phpunit-contao-database.
 *
 * (c) 2012-2015 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage Tests
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/phpunit-contao-database/blob/master/LICENSE LGPL-3.0
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
