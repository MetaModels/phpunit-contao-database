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
