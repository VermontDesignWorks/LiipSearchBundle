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
    private $apiUrl;
    private $maxPerPage;
    private $restrictToSites = [];

    public function __construct($apiKey, $apiUrl, $maxPerPage, array $restrictToSites = [])
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->maxPerPage = $maxPerPage;
        $this->restrictToSites = $restrictToSites;
    }

    public function getPagerfanta($query, $lang)
    {
        $adapter = new BingWebSearchAdapter(
            $this->apiKey,
            $this->apiUrl,
            $query,
            $this->restrictToSites
        );
        $pager = new Pagerfanta($adapter);

        $pager->setMaxPerPage($this->maxPerPage);

        return $pager;
    }

}
