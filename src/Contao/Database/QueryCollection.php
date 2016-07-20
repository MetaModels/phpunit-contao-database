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

namespace MetaModels\Test\Contao\Database;

/**
 * Collection of fake DB queries.
 */
class QueryCollection
{
    /**
     * The list of fake queries.
     *
     * @var FakeQuery[]
     */
    protected $queries;

    /**
     * Create a fake query.
     *
     * @param string $sql The SQL query.
     *
     * @return FakeQuery
     */
    public function theQuery($sql)
    {
        $query           = new FakeQuery($sql);
        $this->queries[] = $query;

        return $query;
    }

    /**
     * Try to find the query matching the given string.
     *
     * @param string $strQuery The query string.
     *
     * @return FakeQuery|null
     */
    public function findQuery($strQuery)
    {
        foreach ($this->queries as $query) {
            if ($query->matches($strQuery)) {
                return $query;
            }
        }

        return null;
    }
}
