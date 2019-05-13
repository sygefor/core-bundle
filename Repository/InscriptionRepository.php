<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/13/19
 * Time: 10:15 AM
 */

namespace Sygefor\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;

/**
 * Class InscriptionRepository.
 */
class InscriptionRepository extends EntityRepository
{
	/**
	 * @param AbstractTrainee $trainee
	 * @param AbstractSession $session
	 *
	 * @return mixed
	 */
	public function getTraineeSessionRegistration(AbstractTrainee $trainee, AbstractSession $session)
	{
		return $this->createQueryBuilder('inscription')
			->leftJoin(AbstractSession::class, 'session', 'WITH', 'inscription.session = session.id')
			->leftJoin(AbstractTrainee::class, 'trainee', 'WITH', 'inscription.trainee = trainee.id')
			->where('session.id = :sessionId')
			->andWhere('trainee.id = :traineeId')
			->setParameter('sessionId', $session->getId())
			->setParameter('traineeId', $trainee->getId())
			->getQuery()->execute();
	}
}
