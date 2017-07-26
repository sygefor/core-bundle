<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 10/07/14
 * Time: 15:23.
 */

namespace Sygefor\Bundle\CoreBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\Material\Material;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/material")
 */
abstract class AbstractMaterialController extends Controller
{
    /**
     * @Route("/{entity_id}/add/{type_entity}/{material_type}/", name="material.add", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     */
    public function addAction(Request $request, $entity_id, $type_entity, $material_type)
    {
        $entity = null;
        $trainingTypes = $this->get('sygefor_core.registry.training_type')->getTypes();

        foreach ($trainingTypes as $type => $infos) {
            if ($type_entity === str_replace('_', '', $type)) {
                $entity = $this->getDoctrine()->getRepository($infos['class'])->find($entity_id);
                break;
            }
        }

        if (!$entity && $type_entity === 'session') {
            $entity = $this->getDoctrine()->getRepository(AbstractSession::class)->find($entity_id);
        }

        if (!$entity) {
            throw \Exception($type_entity.' is not managed for materials');
        }

        if (!$this->get('security.context')->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException('Accès non autorisé');
        }

        $setEntityMethod = $type_entity === 'session' ? 'setSession' : 'setTraining';
        $material = new AbstractMaterial();
        $material->$setEntityMethod($entity);
        $form = $this->createForm(AbstractMaterialType::class, $material);

        // a file is sent : creating a file material
        if ($material_type === null) {
            $material = new AbstractMaterial();
            $material->$setEntityMethod($entity);
            $form = $this->createForm(AbstractMaterialType::class, $material);

            $form->handleRequest($request);
            if ($form->isValid()) {
                $material->$setEntityMethod($entity);

                $em = $this->getDoctrine()->getManager();
                $em->persist($material);
                $em->flush();

                return array('material' => $material);
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/{id}/remove/", name="material.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     * @ParamConverter("material", class="SygeforCoreBundle:Material\Material", options={"id" = "id"})
     */
    public function deleteAction(Material $material)
    {
        if (($material->getTraining() && $this->get('security.context')->isGranted('EDIT', $material->getTraining())) ||
            ($material->getSession() && $this->get('security.context')->isGranted('EDIT', $material->getSession()))) {
            /** @var $em */
            $em = $this->getDoctrine()->getManager();
            try {
                $em->remove($material);
                $em->flush();
            } catch (Exception $e) {
                return array('error' => $e->getMessage());
            }

            return array();
        } else {
            throw new AccessDeniedException('Accès non autorisé');
        }
    }

    /**
     * @Route("/{id}/get/", name="material.get", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     * @ParamConverter("material", class="SygeforCoreBundle:Material\Material", options={"id" = "id"})
     */
    public function getAction($material)
    {
        if (($material->getTraining() && $this->get('security.context')->isGranted('EDIT', $material->getTraining())) ||
            ($material->getSession() && $this->get('security.context')->isGranted('EDIT', $material->getSession()))) {
            if ($material->getType() === 'file') {
                return $material->send();
            } elseif ($material->getType() === 'link') {
                return $material->getUrl();
            }
        } else {
            throw new AccessDeniedException('Accès non autorisé');
        }
    }
}
