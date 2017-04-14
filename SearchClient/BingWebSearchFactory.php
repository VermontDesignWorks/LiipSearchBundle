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

use Liip\SearchBundle\SearchFactoryInterface;
use Pagerfanta\Pagerfanta;

/**
 * Adapter for bing web search API.
 */
class BingWebSearchFactory implements SearchFactoryInterface
{
    private $apiKey;
    private $maxPerPage;
    private $restrictToSites = [];

    public function __construct($apiKey, $maxPerPage, array $restrictToSites = [])
    {
        $this->apiKey = $apiKey;
        $this->maxPerPage = $maxPerPage;
        $this->restrictToSites = $restrictToSites;
    }

    public function getPagerfanta($query, $lang)
    {
        $baseUrl = 'https://api.cognitive.microsoft.com/bing/v5.0/search?q=';
        $term = 'ben';
        $url = sprintf('%s%s+site:vtdesignworks.com', $baseUrl, $term);

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

        // printf('Requesting: %s', $url);
        // echo PHP_EOL;
        // printf('Response Code: %s', $info['http_code']);
        // echo PHP_EOL;
        // printf('Error: %s (%s)', $error['message'], $error['number']);
        // echo PHP_EOL;

        $responseData = $response ? json_decode($response, true) : false;

        // print_r($responseData);
        // exit;

        $results = array_map(
            function (array $resultItemData) {
                return [
                    // 'htmlTitle' => $resultItemData['name'],
                    'title' => $resultItemData['name'],
                    // 'htmlSnippet' => $resultItemData['snippet'],
                    'snippet' => $resultItemData['snippet'],
                    'url' => $resultItemData['url'],
                    // 'site' => parse_url($resultItemData['link'], PHP_URL_HOST),
                    // 'htmlUrl' => $resultItemData['formattedUrl'],
                    // 'index' => $index,
                ];
            },
            $responseData['webPages']['value']
        );

        return new Pagerfanta(new \Pagerfanta\Adapter\ArrayAdapter(
            $results
        ));
    }

}
