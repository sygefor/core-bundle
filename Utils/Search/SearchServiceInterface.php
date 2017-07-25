<?php

namespace Sygefor\Bundle\CoreBundle\Utils\Search;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface SearchServiceInterface.
 */
interface SearchServiceInterface
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function handleRequest(Request $request);
}
