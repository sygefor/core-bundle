<?php

/**
 * Created by PhpStorm.
 * Organization: erwan
 * Date: 5/30/16
 * Time: 5:41 PM.
 */

namespace Sygefor\Bundle\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sygefor\Bundle\CoreBundle\Entity\Organization;
use Sygefor\Bundle\CoreBundle\Form\Type\OrganizationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OrganizationController.
 *
 * @Route("/admin/organizations")
 */
class OrganizationController extends Controller
{
    /**
     * @Route("/", name="organization.index")
     * @Security("is_granted('VIEW', 'SygeforCoreBundle:Organization')")
     */
    public function indexAction()
    {
        $organizations = $this->get('doctrine')->getManager()->getRepository('SygeforCoreBundle:Organization')->findAll();

        return $this->render('organization/index.html.twig', array(
            'organizations' => $organizations,
        ));
    }

    /**
     * @param Request $request
     *
     * @Route("/add", name="organization.add")
     * @Security("is_granted('ADD', 'SygeforCoreBundle:Organization')")
     *
     * @return array|RedirectResponse
     */
    public function addAction(Request $request)
    {
        $organization = new Organization();
        $form = $this->createForm(OrganizationType::class, $organization);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($organization);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Le centre a bien été ajouté.');

                return $this->redirect($this->generateUrl('organization.index'));
            }
        }

        return $this->render('organization/edit.html.twig', array(
            'form' => $form->createView(),
            'organization' => $organization,
        ));
    }

    /**
     * @param Request      $request
     * @param Organization $organization
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="organization.edit", options={"expose"=true})
     * @ParamConverter("organization", class="SygeforCoreBundle:Organization", options={"id" = "id"})
     * @Security("is_granted('EDIT', 'SygeforCoreBundle:Organization')")
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, Organization $organization)
    {
        $form = $this->createForm(OrganizationType::class, $organization);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le centre a bien été mis à jour.');

                return $this->redirect($this->generateUrl('organization.index'));
            }
        }

        return $this->render('organization/edit.html.twig', array(
            'form' => $form->createView(),
            'organization' => $organization,
        ));
    }
}
