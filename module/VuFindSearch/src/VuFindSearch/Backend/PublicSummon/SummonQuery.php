<?php

/**
 * PublicSummon Query class.
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2025.
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
 * @author   VuFind Team
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */

namespace VuFindSearch\Backend\PublicSummon;

/**
 * PublicSummon Query class.
 *
 * @category VuFind
 * @package  Search
 * @author   VuFind Team
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class SummonQuery
{
    /**
     * Search query string
     *
     * @var string|null
     */
    protected $query;

    /**
     * Search options
     *
     * @var array
     */
    protected $options;

    /**
     * Constructor.
     *
     * @param string|null $query   Search query string
     * @param array       $options Search options
     */
    public function __construct(?string $query = null, array $options = [])
    {
        $this->query = $query;
        $this->options = $options;
    }

    /**
     * Get the search query string.
     *
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Set the search query string.
     *
     * @param string|null $query Search query string
     *
     * @return void
     */
    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    /**
     * Get all search options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set all search options.
     *
     * @param array $options Search options
     *
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Get a specific option value.
     *
     * @param string $key     Option key
     * @param mixed  $default Default value if option not found
     *
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Set a specific option value.
     *
     * @param string $key   Option key
     * @param mixed  $value Option value
     *
     * @return void
     */
    public function setOption(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    /**
     * Check if an option exists.
     *
     * @param string $key Option key
     *
     * @return bool
     */
    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Remove an option.
     *
     * @param string $key Option key
     *
     * @return void
     */
    public function removeOption(string $key): void
    {
        unset($this->options[$key]);
    }

    /**
     * Convert the query to an array for API parameters.
     *
     * @return array
     */
    public function toArray(): array
    {
        $options = [
            'q' => $this->query,
            's.ps' => $this->getOption('pageSize'),
            's.pn' => $this->getOption('pageNumber'),
            's.ho' => $this->getOption('holdings') ? 'true' : 'false',
            's.dym' => $this->getOption('didYouMean') ? 'true' : 'false',
            's.l' => $this->getOption('language'),
        ];
        
        if (!empty($this->getOption('idsToFetch'))) {
            $options['s.fids'] = implode(',', (array)$this->getOption('idsToFetch'));
        }
        if (!empty($this->getOption('facets'))) {
            $facets = $this->getOption('facets');
            $options['s.ff'] = is_array($facets) ? implode(',', $facets) : $facets;
        }
        if (!empty($this->getOption('filters'))) {
            $filters = $this->getOption('filters');
            $options['s.fvf'] = is_array($filters) ? implode(',', $filters) : $filters;
        }
        if ($this->getOption('maxTopics') !== false) {
            $options['s.rec.topic.max'] = $this->getOption('maxTopics');
        }
        if (!empty($this->getOption('groupFilters'))) {
            $groupFilters = $this->getOption('groupFilters');
            $options['s.fvgf'] = is_array($groupFilters) ? implode(',', $groupFilters) : $groupFilters;
        }
        if (!empty($this->getOption('rangeFilters'))) {
            $rangeFilters = $this->getOption('rangeFilters');
            $options['s.rf'] = is_array($rangeFilters) ? implode(',', $rangeFilters) : $rangeFilters;
        }
        if (!empty($this->getOption('sort'))) {
            $options['s.sort'] = $this->getOption('sort');
        }
        if ($this->getOption('expand')) {
            $options['s.exp'] = 'true';
        }
        if ($this->getOption('openAccessFilter')) {
            $options['s.oaf'] = 'true';
        }
        if ($this->getOption('highlight')) {
            $options['s.hl'] = 'true';
            $options['s.hs'] = $this->getOption('highlightStart');
            $options['s.he'] = $this->getOption('highlightEnd');
        } else {
            $options['s.hl'] = 'false';
            $options['s.hs'] = '';
            $options['s.he'] = '';
        }
        
        return $options;
    }

    /**
     * ArrayAccess implementation for backward compatibility.
     */

    /**
     * Check if offset exists.
     *
     * @param mixed $offset Offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $offset === 'query' || isset($this->options[$offset]);
    }

    /**
     * Get offset value.
     *
     * @param mixed $offset Offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($offset === 'query') {
            return $this->query;
        }
        return $this->options[$offset] ?? null;
    }

    /**
     * Set offset value.
     *
     * @param mixed $offset Offset
     * @param mixed $value  Value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === 'query') {
            $this->query = $value;
        } else {
            $this->options[$offset] = $value;
        }
    }

    /**
     * Unset offset.
     *
     * @param mixed $offset Offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        if ($offset === 'query') {
            $this->query = null;
        } else {
            unset($this->options[$offset]);
        }
    }

    /**
     * Escape a parameter for use in a query.
     *
     * @param string $input Parameter to escape
     *
     * @return string
     */
    public static function escapeParam(string $input): string
    {
        // Remove any existing escaping:
        $input = str_replace('\\', '', $input);
        
        // Escape special characters that need to be escaped in Summon queries:
        $specialChars = ['+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']',
                        '^', '"', '~', '*', '?', ':', '\\', '/'];
        foreach ($specialChars as $char) {
            $input = str_replace($char, '\\' . $char, $input);
        }
        
        return $input;
    }
}