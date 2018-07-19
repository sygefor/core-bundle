<?php

namespace Sygefor\Bundle\CoreBundle\BatchOperations\SemesteredTraining;

use Sygefor\Bundle\CoreBundle\BatchOperations\Training\ConvertTypeBatchOperation as BaseConvertTypeBatchOperation;
use Sygefor\Bundle\CoreBundle\Model\SemesteredTraining;

class SemesteredTrainingConvertTypeBatchOperation extends BaseConvertTypeBatchOperation
{
    protected function getObjectList(array $idList = array())
    {
        return SemesteredTraining::getTrainingsByIds($idList, $this->em, array());
    }
}
