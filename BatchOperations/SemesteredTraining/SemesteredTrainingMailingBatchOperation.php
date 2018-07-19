<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */

namespace Sygefor\Bundle\CoreBundle\BatchOperations\SemesteredTraining;

use Sygefor\Bundle\CoreBundle\BatchOperations\Generic\MailingBatchOperation as BaseMailingBatchOperation;
use Sygefor\Bundle\CoreBundle\Model\SemesteredTraining;

/**
 * Class MailingBatchOperation.
 */
class SemesteredTrainingMailingBatchOperation extends BaseMailingBatchOperation
{
    /**
     * Getting objects list.
     *
     * @param array $idList
     *
     * @return \Sygefor\Bundle\CoreBundle\Model\SemesteredTraining[]
     */
    protected function getObjectList($idList)
    {
        return SemesteredTraining::getSemesteredTrainingsByIds($this->idList, $this->em);
    }
}
