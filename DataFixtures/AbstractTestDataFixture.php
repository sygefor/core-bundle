<?php

namespace Sygefor\Bundle\CoreBundle\DataFixtures;

/**
 * Class AbstractTestDataFixture.
 */
abstract class AbstractTestDataFixture extends AbstractDataFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEnvironments()
    {
        return array('test');
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
