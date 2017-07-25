<?php

namespace Sygefor\Bundle\CoreBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sygefor\Bundle\CoreBundle\Form\Type\ChangeOrganizationType;
use Sygefor\Bundle\CoreBundle\Entity\AbstractInstitution;
use Sygefor\Bundle\CoreBundle\Form\RemoveInstitutionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/institution")
 */
abstract class AbstractInstitutionController extends Controller
{
    protected $institutionClass = AbstractInstitution::class;

    /**
     * @Route("/search", name="institution.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request)
    {
        $search = $this->get('sygefor_institution.search');
        $search->handleRequest($request);

        return $search->search();
    }

    /**
     * @Route("/create", name="institution.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request)
    {
        /** @var AbstractInstitution $institution */
        $institution = new $this->institutionClass();
        $institution->setOrganization($this->getUser()->getOrganization());

        //institution can't be created if user has no rights for it
        if (!$this->get('security.context')->isGranted('CREATE', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($institution::getFormType(), $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($institution);
                $em->flush();
            }
        }

        return array('institution' => $institution, 'form' => $form->createView());
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the institution.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="institution.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="institution", permissions="VIEW")
     * @ParamConverter("institution", class="SygeforCoreBundle:AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request, AbstractInstitution $institution)
    {
        if (!$this->get('security.context')->isGranted('EDIT', $institution)) {
            if ($this->get('security.context')->isGranted('VIEW', $institution)) {
                return array('institution' => $institution);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($institution::getFormType(), $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->persist($institution);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'institution' => $institution);
    }

    /**
     * @Route("/{id}/changeorg", name="institution.changeorg", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="institution", permissions="EDIT")
     * @ParamConverter("institution", class="SygeforCoreBundle:AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function changeOrganizationAction(Request $request, AbstractInstitution $institution)
    {
        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_inscription.rights.inscription.all.update')) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(ChangeOrganizationType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'institution' => $institution);
    }

    /**
     * @param Request             $request
     * @param AbstractInstitution $institution
     *
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="institution.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("institution", class="SygeforCoreBundle:AbstractInstitution", options={"id" = "id"})
     * @SecureParam(name="institution", permissions="DELETE")
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     *
     * @return mixed
     */
    public function removeAction(Request $request, AbstractInstitution $institution)
    {
        $em = $this->getDoctrine()->getManager();
        $trainees = $em->getRepository('SygeforCoreBundle:AbstractTrainee')->findBy(array(
            'institution' => $institution,
        ));

        $form = new RemoveInstitutionType($institution, count($trainees));
        $form = $this->createForm($form);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $institutionReplacement = $form->get('replacement')->getData();
                if ($institutionReplacement || count($trainees) === 0) {
                    foreach ($trainees as $trainee) {
                        $trainee->setInstitution($institutionReplacement);
                    }
                    $em->flush();
                }

                $em->remove($institution);
                $em->flush();
                $this->get('fos_elastica.index')->refresh();

                return $this->redirect($this->generateUrl('institution.search'));
            }
        }

        return array('form' => $form->createView(), 'institutionTrainees' => count($trainees));
    }
}
