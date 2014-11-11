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
 * A fake database result.
 */
class FakeResult
{
    /**
     * The rows.
     *
     * @var array
     */
    protected $rows = array();

    /**
     * Add a row.
     *
     * @param array    $data  The row data.
     *
     * @param null|int $rowId The id for the row.
     *
     * @return FakeResult
     */
    public function addRow($data, $rowId = null)
    {
        if (($rowId === null) && isset($data['id'])) {
            $rowId = $data['id'];
        }

        if ($rowId === null) {
            $rowId = count($this->rows);
        }

        $this->rows[$rowId] = $data;

        return $this;
    }

    /**
     * Add rows.
     *
     * @param array $data The row data.
     *
     * @return FakeResult
     */
    public function addRows($data)
    {
        foreach ($data as $key => $row) {
            $this->addRow($row, isset($row['id']) ? $row['id'] : $key);
        }

        return $this;
    }



    /**
     * Retrieve the row with the given index.
     *
     * @param int $index The index.
     *
     * @return array
     */
    public function getRow($index)
    {
        $keys = array_keys($this->rows);

        return isset($keys[$index]) ? $this->rows[$keys[$index]] : null;
    }

    /**
     * Retrieve the amount of rows in the buffer.
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }
}
