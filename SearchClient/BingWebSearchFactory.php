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

    public function getPagerfanta($query, $lang)
    {
    }

}
