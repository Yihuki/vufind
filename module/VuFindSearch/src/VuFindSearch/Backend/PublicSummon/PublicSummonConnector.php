<?php

/**
 * PublicSummon Search API Interface (Guzzle and Psr implementation)
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
 * @author   Sambhav Pokharel <sambhav.pokharel@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace VuFindSearch\Backend\PublicSummon;

use GuzzleHttp\Client as HttpClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Guzzle and PSR-compliant connector for PublicSummon API
 *
 * @category VuFind
 * @package  Search
 * @author   Sambhav Pokharel <sambhav.pokharel@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class PublicSummonConnector implements LoggerAwareInterface
{
    const IDENTIFIER_ID = 1;
    const IDENTIFIER_BOOKMARK = 2;

    /**
     * HTTP client instance
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * Logger instance.
     *
     * @var ?LoggerInterface
     */
    protected $logger = null;

    /**
     * The URL of the PublicSummon API server
     *
     * @var string
     */
    protected $host = 'host';

    /**
     * The API version to use
     *
     * @var string
     */
    protected $version = 'api';

    /**
     * Is the end user authenticated or not?
     *
     * @var bool
     */
    protected $authedUser = false;

    /**
     * Acceptable response type from Summon
     * Currently summon supports json and xml
     *
     * @var string
     */
    protected $responseType = "json";

    /**
     * Constructor.
     *
     * @param array      $options Options for the connector
     * @param HttpClient $client  Optional HTTP client to use
     */
    public function __construct(array $options = [], ?HttpClient $client = null)
    {
        $this->client = $client ?? new HttpClient();
        
        // Process options
        if (isset($options['authedUser'])) {
            $this->authedUser = $options['authedUser'];
        }
        if (isset($options['host'])) {
            $this->host = $options['host'];
        }
        if (isset($options['version'])) {
            $this->version = $options['version'];
        }
        if (isset($options['responseType'])) {
            $this->responseType = $options['responseType'];
        }
    }

    /**
     * Sets the logger instance.
     *
     * @param LoggerInterface $logger The logger instance to set.
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Prints a debug message if debug is enabled.
     *
     * @param string $msg The message to debug.
     *
     * @return void
     */
    protected function debugPrint($msg)
    {
        if ($this->logger) {
            $this->logger->debug($msg);
        }
    }

    /**
     * Retrieves a document specified by the ID or bookmark.
     *
     * @param string $id     The document to retrieve from the PublicSummon API
     * @param bool   $raw    Return raw (true) or processed (false) response?
     * @param int    $idType Constant representing type of $id (either standard
     * identifier -- IDENTIFIER_ID -- or bookmark -- IDENTIFIER_BOOKMARK).
     *
     * @return array The requested resource
     */
    public function getRecord($id, $raw = false, $idType = self::IDENTIFIER_ID)
    {
        $this->debugPrint("Get Record: $id");

        // Query String Parameters
        $options = $idType === self::IDENTIFIER_BOOKMARK
            ? ['bookmark' => $id]
            : ['fids' => $id];
        $options['s.role'] = $this->authedUser ? 'authenticated' : 'none';
        return $this->call($options, 'search', 'GET', $raw);
    }

    /**
     * Execute a search.
     *
     * @param SummonQuery $query     Query object
     * @param bool        $returnErr On fatal error, should we fail
     * outright (false) or treat it as an empty result set with an error key set
     * (true)?
     * @param bool        $raw       Return raw (true) or processed
     * (false) response?
     *
     * @return array An array of query results
     */
    public function query(SummonQuery $query, $returnErr = false, $raw = false)
    {
        $options = $query->toArray();

        $this->debugPrint('Query: ' . print_r($options, true));

        try {
            $result = $this->call($options, 'search', 'GET', $raw);
        } catch (\Exception $e) {
            if ($returnErr) {
                return [
                    'recordCount' => 0,
                    'documents' => [],
                    'errors' => $e->getMessage()
                ];
            } else {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * Submit REST Request
     *
     * @param array  $params  An array of parameters for the request
     * @param string $service The API Service to call
     * @param string $method  The HTTP Method to use
     * @param bool   $raw     Return raw (true) or processed (false) response?
     *
     * @throws \Exception
     * @return array          The PublicSummon API response
     */
    protected function call($params = [], $service = 'search', $method = 'GET', $raw = false)
    {
        $baseUrl = $this->host . '/' . $this->version . '/' . $service;

        // Build Query String
        $query = [];
        foreach ($params as $function => $value) {
            if (is_array($value)) {
                foreach ($value as $additional) {
                    $additional = urlencode($additional);
                    $query[] = "$function=$additional";
                }
            } else {
                $value = urlencode($value);
                $query[] = "$function=$value";
            }
        }
        asort($query);
        $queryString = implode('&', $query);

        // Build Headers (no authentication required)
        $headers = [
            'Accept' => 'application/' . $this->responseType,
            'x-summon-date' => gmdate('D, d M Y H:i:s T'),
            'Host' => parse_url($this->host, PHP_URL_HOST)
        ];

        $this->debugPrint("{$method}: {$baseUrl}?{$queryString}");

        $options = ['headers' => $headers];

        if ($method == 'GET') {
            $baseUrl .= '?' . $queryString;
        } elseif ($method == 'POST') {
            $options['body'] = $queryString;
            $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $result = $this->client->request($method, $baseUrl, $options);

        if ($result->getStatusCode() < 200 || $result->getStatusCode() >= 300) {
            throw new \Exception($result->getBody()->getContents());
        }

        $response = $result->getBody()->getContents();

        if (!$raw) {
            $response = $this->process($response);
        }

        return $response;
    }

    /**
     * Perform normalization and analysis of Summon return value.
     *
     * @param string $input The raw response from Summon
     *
     * @throws \Exception
     * @return array       The processed response from Summon
     */
    protected function process($input)
    {
        if ($this->responseType !== "json") {
            return $input;
        }

        // Unpack JSON Data
        $result = json_decode($input, true);

        // Catch decoding errors -- turn a bad JSON input into an empty result set
        // containing an appropriate error code.
        if (!$result) {
            $result = [
                'recordCount' => 0,
                'documents' => [],
                'errors' => [
                    [
                        'code' => 'PHP-Internal',
                        'message' => 'Cannot decode JSON response: ' . $input
                    ]
                ]
            ];
        }

        // Detect errors
        if (isset($result['errors']) && is_array($result['errors'])) {
            $errors = [];
            foreach ($result['errors'] as $current) {
                $errors[] = "{$current['code']}: {$current['message']}";
            }
            $msg = 'Unable to process query<br />PublicSummon returned: ' .
                implode('<br />', $errors);
            throw new \Exception($msg);
        }

        return $result;
    }
}