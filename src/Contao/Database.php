<?php

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

namespace MetaModels\Test\Contao;

use MetaModels\Test\Contao\Database\QueryCollection;
use MetaModels\Test\Contao\Database\Statement;

/**
 * Mock-up micro class to simulate the Contao Database class.
 */
class Database extends \Contao\Database
{
    /**
     * The statements and results.
     *
     * @var QueryCollection
     */
    protected $queries;

    /**
     * Set us as database driver.
     *
     * @return void
     */
    public static function register()
    {
        if (!class_exists('Database', false)) {
            class_alias('MetaModels\Test\Contao\Database', 'Database');
            class_alias('Contao\Database\Statement', 'Database\Statement');
            class_alias('Contao\Database\Result', 'Database\Result');
        }
    }

    /**
     * Establish the database connection.
     *
     * @param array $arrConfig A configuration array.
     *
     * @throws \Exception If a connection cannot be established.
     */
    protected function __construct(array $arrConfig)
    {
        $this->arrConfig = $arrConfig;
        $this->connect();
    }

    /**
     * Set the query collection to use.
     *
     * @param QueryCollection $collection The query collection.
     *
     * @return Database
     */
    public function setQueryCollection($collection)
    {
        $this->queries = $collection;

        return $this;
    }

    /**
     * Retrieve the query collection.
     *
     * @return QueryCollection
     */
    public function getQueryCollection()
    {
        if ($this->queries === null) {
            $this->setQueryCollection(new QueryCollection());
        }

        return $this->queries;
    }

    /**
     * Instantiate the Database object (Factory).
     *
     * @param array $arrCustom A configuration array.
     *
     * @return Database The Database object
     */
    public static function getNewTestInstance(array $arrCustom = null)
    {
        if ($arrCustom === null) {
            $arrCustom = array(
                'dbPconnect' => false
            );
        }

        return new static($arrCustom);
    }

    /**
     * {@inheritdoc}
     *
     * @return Database The Database object
     */
    public static function getInstance(array $arrCustom = null)
    {
        if (!isset(static::$arrInstances['test'])) {
            static::$arrInstances['test'] = static::getNewTestInstance($arrCustom);
        }

        return static::$arrInstances['test'];
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function connect()
    {
        // No op.
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function disconnect()
    {
        // No op.
    }

    // @codingStandardsIgnoreStart - We have methods here that are not in camel case.
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function get_error()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function find_in_set($strKey, $varSet, $blnIsField = false)
    {
        if ($blnIsField) {
            return 'FIND_IN_SET(' . $strKey . ', ' . $varSet . ')';
        }

        return 'FIND_IN_SET(' . $strKey . ', \'' . str_replace('\'', '\\\'', $varSet) . '\')';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException As currently unsupported.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function list_fields($strTable)
    {
        throw new \RuntimeException('currently unsupported in test suite.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException As currently unsupported.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function set_database($strDatabase)
    {
        throw new \RuntimeException('currently unsupported in test suite.');
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function begin_transaction()
    {
        // No op.
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function commit_transaction()
    {
        // No op.
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function rollback_transaction()
    {
        // No op.
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function lock_tables($arrTables)
    {
        // No op.
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function unlock_tables()
    {
        // No op.
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException As currently unsupported.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function get_size_of($strTable)
    {
        throw new \RuntimeException('currently unsupported in test suite.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException As currently unsupported.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function get_next_id($strTable)
    {
        throw new \RuntimeException('currently unsupported in test suite.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException As currently unsupported.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function get_uuid()
    {
        throw new \RuntimeException('currently unsupported in test suite.');
    }
    // @codingStandardsIgnoreEnd - methods that are not in camel case.

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function createStatement($resConnection, $blnDisableAutocommit)
    {
        $statement = new Statement($this);

        return $statement;
    }
}
