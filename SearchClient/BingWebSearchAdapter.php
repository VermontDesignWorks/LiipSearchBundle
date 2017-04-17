<?php

/*
 * This file is part of the LiipSearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\SearchClient;

use Liip\SearchBundle\Exception\SearchException;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Adapter for Bing web search API.
 */
class BingWebSearchAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string[]|array
     */
    private $restrictToSites = [];

    /**
     * @var bool Number of results found.
     */
    private $totalResults = false;

    /**
     * @param string      $apiKey
     * @param string      $apiUrl
     * @param string      $query
     * @param array $restrictToSites
     */
    public function __construct($apiKey, $apiUrl, $query, array $restrictToSites = [])
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->query = $query;
        $this->restrictToSites = $restrictToSites;
    }

    /**
     * Get search results
     *
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $results = $this->executeSearch($offset, $length);

        // FIXME: Hopefully this doesnt hide errors
        if (!array_key_exists('webPages', $results)) {
            $this->totalResults = 0;

            return [];
        }

        $this->totalResults = $results['webPages']['totalEstimatedMatches'];
        
        return $this->extractResults($results['webPages']['value']);
    }

    private function executeSearch($offset, $count)
    {
        $url = sprintf(
            '%s?%s', 
            $this->apiUrl,
            http_build_query([
                'mkt' => 'en-US',
                'count' => $count,
                'q' => $this->buildQuery(),
                'offset' => $offset,
            ])
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            sprintf('Ocp-Apim-Subscription-Key: %s', $this->apiKey)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = [
            'number' => curl_errno($ch),
            'message' => curl_error($ch),
        ];
        curl_close($ch);

        if ($error['number'] !== 0) {
            throw new SearchException(sprintf(
                'Curl error %d: %s',
                $error['number'],
                $error['message']
            ));
        }

        if ($info['http_code'] !== 200) {
            throw new SearchException(sprintf(
                'Non-200 response: %s',
                $info['http_code']
            ));
        }

        if (empty($response)) {
            throw new SearchException(
                'Empty response with no curl error and status 200. This should never happen.'
            );
        }

        $responseData = json_decode($response, true);

        if (is_null($responseData)) {
            throw new SearchException(
                'Failed to decode response json'
            );
        }

        return $responseData;
    }

    /**
     * @param array $results 
     * @return array
     */
    private function extractResults(array $results)
    {
        return array_map(
            function (array $resultItemData) {
                return [
                    'title' => $resultItemData['name'],
                    'htmlTitle' => $this->highlightKeywords($resultItemData['name']),
                    'htmlSnippet' => $this->highlightKeywords($resultItemData['snippet']),
                    'snippet' => $resultItemData['snippet'],
                    'url' => $resultItemData['displayUrl'],
                ];
            },
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        if (false === $this->totalResults) {
            $this->getSlice(0, 1);
        }

        return $this->totalResults;
    }

    /**
     * Build the bing search query, including restricting to certain sites
     * 
     * @return string
     */
    private function buildQuery()
    {
        if (empty($this->restrictToSites)) {
            return $this->query;
        }

        return sprintf(
            '%s (site:%s)',
            $this->query,
            implode(
                ' OR site:',
                $this->restrictToSites
            )
        );
    }

    /**
     * Bing web search doesnt provide an html snippet with search terms highlighted
     * This method wraps b tags around any of the words from the query
     * 
     * @param string $text 
     * @return string
     */
    private function highlightKeywords($text)
    {
        $words = explode(' ', $this->query);

        $highlightedText = $text;
        foreach ($words as $word) {
            $highlightedText = preg_replace(
                '/'.preg_quote($word).'/i',
                '<b>$0</b>',
                $highlightedText
            );
        }

        return $highlightedText;
    }
}
