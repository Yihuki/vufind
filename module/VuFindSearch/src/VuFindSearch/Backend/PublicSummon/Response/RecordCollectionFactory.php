<?php

/**
 * PublicSummon record collection factory.
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

namespace VuFindSearch\Backend\PublicSummon\Response;

use VuFindSearch\Backend\PublicSummon\Response\RecordCollection;
use VuFindSearch\Response\RecordCollectionFactoryInterface;
use VuFindSearch\Response\RecordCollectionInterface;

/**
 * PublicSummon record collection factory.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class RecordCollectionFactory implements RecordCollectionFactoryInterface
{
    /**
     * Factory to turn data into a record object.
     *
     * @var callable
     */
    protected $recordFactory;

    /**
     * Constructor.
     *
     * @param callable $recordFactory Record factory callback
     */
    public function __construct(callable $recordFactory)
    {
        $this->recordFactory = $recordFactory;
    }

    /**
     * Return record collection.
     *
     * @param array $response Summon response
     *
     * @return RecordCollectionInterface
     */
    public function factory($response)
    {
        if (!is_array($response)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unexpected type of value: Expected array, got %s',
                    gettype($response)
                )
            );
        }

        $collection = new RecordCollection($response);

        // Extract documents from response
        $documents = $response['documents'] ?? [];
        $recordFactory = $this->recordFactory;

        foreach ($documents as $doc) {
            $collection->add($recordFactory($doc));
        }

        return $collection;
    }
}