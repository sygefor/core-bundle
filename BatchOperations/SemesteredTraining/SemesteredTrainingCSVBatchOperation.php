<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:43.
 */

namespace Sygefor\Bundle\CoreBundle\BatchOperations\SemesteredTraining;

use Sygefor\Bundle\CoreBundle\BatchOperations\Generic\CSVBatchOperation as BaseCSVBatchOperation;
use Sygefor\Bundle\CoreBundle\Model\SemesteredTraining;

class SemesteredTrainingCSVBatchOperation extends BaseCSVBatchOperation
{
    /**
     * @param $idList
     *
     * @return \Sygefor\Bundle\CoreBundle\Model\SemesteredTraining[]
     */
    protected function getObjectList($idList)
    {
        return SemesteredTraining::getSemesteredTrainingsByIds($idList, $this->em);
    }
}
