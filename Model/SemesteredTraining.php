<?php

/**
 * Class used to create objects
 * Created by PhpStorm.
 * User: maxime
 * Date: 16/04/14
 * Time: 11:13.
 */

namespace Sygefor\Bundle\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTraining;

class SemesteredTraining
{
    /**
     * @var int
     */
    protected $year;

    /**
     * @var int
     */
    protected $semester;

    /**
     * @var AbstractTraining
     */
    protected $training;

    /**
     * @var AbstractSession[]
     */
    protected $sessions;

    public function __construct($year, $semester, $training, $sessions = null)
    {
        $this->year = $year;
        $this->semester = $semester;
        $this->training = $training;
        $this->setSessions($sessions);
    }

    /**
     * @param int $semester
     */
    public function setSemester($semester)
    {
        $this->semester = $semester;
    }

    /**
     * @return int
     */
    public function getSemester()
    {
        return $this->semester;
    }

    /**
     * @param array $sessions
     */
    public function setSessions($sessions = null)
    {
        if (is_array($sessions) && !empty($sessions)) {
            $this->sessions = $sessions;
            $this->orderSessions();
        }
        else {
            $this->setSessionsFromTrainingAndDate();
        }
    }

    /**
     * @return array
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param mixed $training
     */
    public function setTraining($training)
    {
        $this->training = $training;
    }

    /**
     * @return mixed
     */
    public function getTraining()
    {
        return $this->training;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->training->getId() . '_' . $this->getYear() . '_' . $this->getSemester();
    }

    /**
     * @return AbstractSession
     */
    public function getLastSession()
    {
        if (empty($this->sessions)) {
            return null;
        }
        $now = new \DateTime();

        $result = null;
        $maxdif = 9999999999;
        foreach ($this->sessions as $session) {
            $dif = $now->getTimestamp() - $session->getDateBegin()->getTimeStamp();
            if (($dif > 0) && ($dif < $maxdif)) {
                $result = $session;
                $maxdif = $dif;
            }
        }

        return $result;
    }

    /**
     * @return AbstractSession
     */
    public function getNextSession()
    {
        if (empty($this->sessions)) {
            return null;
        }
        $now = new \DateTime();

        $result = null;
        $maxdif = 9999999999;
        foreach ($this->sessions as $session) {
            $dif = $session->getDateBegin()->getTimestamp() - $now->getTimeStamp();
            if (($dif > 0) && ($dif < $maxdif)) {
                $result = $session;
                $maxdif = $dif;
            }
        }

        return $result;
    }

    /**
     * Returns the number of sessions belonging to semesteredtraining.
     *
     * @return int
     */
    public function getSessionsCount()
    {
        if (empty($this->sessions)) {
            return 0;
        }

        return count($this->sessions);
    }

    /**
     * Get array of trainer.
     *
     * @return array
     */
    public function getTrainers()
    {
        $trainers = array();
        if ($this->sessions) {
            foreach ($this->sessions as $session) {
                if ($session->getParticipations() && $session->getParticipations()->count() > 0) {
                    foreach ($session->getParticipations() as $participation) {
                        // do not add several times the same trainer
                        $trainers[$participation->getTrainer()->getId()] = $participation->getTrainer();
                    }
                }
            }
        }

        return $trainers;
    }

    /**
     * builds and returns SemesteredTraining objects array corresponding to Training object.
     *
     * @param AbstractTraining $training
     *
     * @return array
     */
    public static function getSemesteredTrainingsForTraining(AbstractTraining $training)
    {
        /** @var AbstractSession[] $sessions */
        $sessions = $training->getSessions();

        // sorting sessions per year/semester
        $orderedSessions = array();
        if (count($sessions) !== 0) {
            foreach ($sessions as $session) {
                //if (!$session ){die();}
                /** @var \DateTime $date */
                $date = $session->getDateBegin();
                $year = $date->format('Y');
                $semester = ($date->format('m') <= 6) ? 1 : 2;
                if (!isset($orderedSessions[$year])) {
                    $orderedSessions[$year] = array();
                }
                if (!isset($orderedSessions[$year][$semester])) {
                    $orderedSessions[$year][$semester] = array();
                }
                $orderedSessions[$year][$semester][] = $session;
            }

            // SemesteredTrainings objects are built around each sessions list
            $semTrainings = array();
            foreach ($orderedSessions as $year => $semesters) {
                foreach ($semesters as $sem => $sessions) {
                    $tempSemTraining = new self($year, $sem, $training);
                    $tempSemTraining->setSessions($sessions);

                    $semTrainings[] = $tempSemTraining;
                }
            }

            return $semTrainings;
        }

        $year = $training->getFirstSessionPeriodYear();
        $semester = $training->getFirstSessionPeriodSemester();

        $semTraining = new self($year, $semester, $training, array());

        return array($semTraining);
    }

    /**
     * Return an array of training and remove duplicates from semestered training list.
     *
     * @param array $idList
     * @param EntityManager $em
     * @param array $excludedTypes
     */
    public static function getTrainingsByIds(array $idList, EntityManager $em, $excludedTypes)
    {
        $arrayIds = array();
        foreach ($idList as $semesteredTrainingId) {
            $arrayIds[] = explode('_', $semesteredTrainingId)[0];
        }
        $arrayIds = array_unique($arrayIds);

        $allEntities = $em->getRepository(AbstractTraining::class)->findBy(array('id' => $arrayIds));
        $notMeetingEntities = array();
        foreach ($allEntities as $entity) {
            if (!in_array($entity->getType(), $excludedTypes, true)) {
                $notMeetingEntities[] = $entity;
            }
        }

        return $notMeetingEntities;
    }

    /**
     * Returns an array of semestered trainings corresponding to given list of ids.
     *
     * @param array $idList
     * @param EntityManager $em
     *
     * @return SemesteredTraining[]
     */
    public static function getSemesteredTrainingsByIds(array $idList, EntityManager $em)
    {
        // building DQL query to get needed sessions objects
        $qb = $em->createQueryBuilder()
            ->select('s')
            ->from(AbstractTraining::class, 't')
            ->leftJoin(AbstractSession::class, 's', Join::WITH, 't = s.training');

        $paramCount = 0;
        $parameters = array();
        foreach ($idList as $tId) {
            $params = explode('_', $tId);
            if (count($params) === 3) {
                $dateFrom = ($params[2] === 2) ? $params[1] . '-01-07 00:00:00' : $params[1] . '-01-01 00:00:00';
                $dateTo = ($params[2] === 2) ? $params[1] . '-31-12 23:59:59' : $params[1] . '-30-06 23:59:59';

                $qb->orWhere('( t.id = :id' . $paramCount . ' AND s.dateBegin < :dateTo' . $paramCount . ' AND s.dateBegin > :dateFrom' . $paramCount . ')');
                $parameters = array_merge($parameters, array(
                    'id' . $paramCount => $params[0],
                    'dateTo' . $paramCount => $dateTo,
                    'dateFrom' . $paramCount => $dateFrom,
                ));
                ++$paramCount;
            }
        }
        $qb->setParameters($parameters);
        $tmpArray = $qb->getQuery()->getResult();

        // objects are grouped by training / year / semester
        $sessions = array();
        foreach ($tmpArray as $re) {
            if (!empty($re)) {
                $ys = self::getYearAndSemesterFromDate($re->getDateBegin());
                $tId = $re->getTraining()->getId();
                if (!isset($sessions[$tId])) {
                    $sessions[$tId] = array();
                }
                if (!isset($sessions[$tId][$ys[0]])) {
                    $sessions[$tId][$ys[0]] = array();
                }
                if (!isset($sessions[$tId][$ys[0]][$ys[1]])) {
                    $sessions[$tId][$ys[0]][$ys[1]] = array();
                }
                $sessions[$tId][$ys[0]][$ys[1]][] = $re;
            }
        }

        $semTrains = array();
        // for each training / year / semester, a SemesteredTraining object is built
        foreach ($idList as $id) {
            $params = explode('_', $id);

            if (count($params) === 3) {
                if (!empty($sessions[$params[0]][$params[1]][$params[2]])) {
                    // getting sessions
                    $tmpSessions = $sessions[$params[0]][$params[1]][$params[2]];
                    $semTrains[] = new self($params[1], $params[2], $tmpSessions[0]->getTraining(), $tmpSessions);
                }
                else {
                    $semTrains[] = new self($params[1], $params[2], $em->getRepository(AbstractTraining::class)->find($params[0]), array());
                }
            }
        }

        return $semTrains;
    }

    /**
     * helper for getting year+ semester.
     *
     * @param \DateTime $date
     *
     * @return array
     */
    public static function getYearAndSemesterFromDate(\DateTime $date)
    {
        $year = $date->format('Y');
        $semester = ($date->format('m') <= 6) ? 1 : 2;

        return array($year, $semester);
    }


    /**
     * sets the sessions list given the current objects training and year/semester values.
     */
    protected function setSessionsFromTrainingAndDate()
    {
        $sessions = $this->training->getSessions();
        $tmpSessions = array();
        if (!empty($sessions)) {
            foreach ($sessions as $session) {
                /** @var \DateTime $date */
                $date = $session->getDateBegin();

                $year = $date->format('Y');
                $semester = ($date->format('m') <= 6) ? 1 : 2;

                if ($year === $this->year && $semester === $this->semester) {
                    $tmpSessions[] = $session;
                }
            }
            $this->sessions = $tmpSessions;
            $this->orderSessions();
        }
    }

    /**
     * ordering.
     */
    protected function orderSessions()
    {
        @usort($this->sessions, function ($a, $b) {
            $ad = $a->getDateBegin();
            $bd = $b->getDateBegin();

            if ($ad === $bd) {
                return 0;
            }

            return $ad < $bd ? 1 : -1;
        });
    }
}
