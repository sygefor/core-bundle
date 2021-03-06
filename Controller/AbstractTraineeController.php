<?php

namespace Sygefor\Bundle\CoreBundle\Controller;

use Html2Text\Html2Text;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sygefor\Bundle\CoreBundle\Form\Type\ChangeOrganizationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class TraineeController.
 *
 * @Route("/trainee")
 */
abstract class AbstractTraineeController extends Controller
{
    /**
     * @var string
     */
    protected $traineeClass = AbstractTrainee::class;

    /**
     * @param Request $request
     *
     * @Route("/search", name="trainee.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Security("is_granted('VIEW', 'SygeforCoreBundle:AbstractTrainee')")
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     *
     * @throws \Exception
     */
    public function searchAction(Request $request)
    {
        $search = $this->get('sygefor_trainee.search');
        $search->handleRequest($request);

        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainee.all.view')) {
            $search->addTermFilter('organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search();
    }

    /**
     * @param Request $request
     *
     * @Route("/create", name="trainee.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Security("is_granted('CREATE', 'SygeforCoreBundle:AbstractTrainee')")
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        /** @var AbstractTrainee $trainee */
        $trainee = new $this->traineeClass();
        $trainee->setOrganization($this->getUser()->getOrganization());

        //trainee can't be created if user has no rights for it
        if (!$this->get('security.context')->isGranted('CREATE', $trainee)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($trainee::getFormType(), $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($trainee);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'trainee' => $trainee);
    }

    /**
     * @param Request         $request
     * @param AbstractTrainee $trainee
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="trainee.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function viewAction(Request $request, AbstractTrainee $trainee)
    {
        // access right is checked inside controller, so to be able to send specific error message
        if (!$this->get('security.context')->isGranted('EDIT', $trainee)) {
            if ($this->get('security.context')->isGranted('VIEW', $trainee)) {
                return array('trainee' => $trainee);
            }

            throw new AccessDeniedException("Vous n'avez pas accès aux informations détaillées de cet utilisateur");
        }

        $form = $this->createForm($trainee::getFormType(), $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($trainee);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'trainee' => $trainee);
    }

    /**
     * @param Request         $request
     * @param AbstractTrainee $trainee
     *
     * @Route("/{id}/toggleActivation", requirements={"id" = "\d+"}, name="trainee.toggleActivation", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     * @Method("POST")
     *
     * @return array
     *
     * @throws \Html2Text\Html2TextException
     * @throws \Twig_Error
     */
    public function toggleActivationAction(Request $request, AbstractTrainee $trainee)
    {
        //access right is checked inside controller, so to be able to send specific error message
        if (!$this->get('security.context')->isGranted('EDIT', $trainee)) {
            throw new AccessDeniedException("Vous n'avez pas accès aux informations détaillées de cet utilisateur");
        }

        $trainee->setIsActive(!$trainee->getIsActive());
        $this->getDoctrine()->getManager()->flush();

        if ($trainee->getIsActive()) {
            $body = $this->get('templating')->render('trainee/admin_activation.html.twig', array('trainee' => $trainee));

            // send the mail
            $message = \Swift_Message::newInstance(null, null, 'text/html', null)
                ->setFrom($this->container->getParameter('mailer_from'), $this->container->getParameter('mailer_from_name'))
                ->setReplyTo($trainee->getOrganization()->getEmail())
                ->setSubject('SYGEFOR : activiation de votre compte')
                ->setTo($trainee->getEmail())
                ->setBody($body);
            $message->addPart(Html2Text::convert($message->getBody()), 'text/plain');
            $this->get('mailer')->send($message);
        }

        return array('trainee' => $trainee);
    }

    /**
     * @param Request         $request
     * @param AbstractTrainee $trainee
     *
     * @Route("/{id}/changepwd", name="trainee.changepwd", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="trainee", permissions="EDIT")
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function changePasswordAction(Request $request, AbstractTrainee $trainee)
    {
        $form = $this->createFormBuilder($trainee)
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'constraints' => array(
                    new Length(array('min' => 8)),
                    new NotBlank(),
                ),
                'required' => true,
                'invalid_message' => 'Les mots de passe doivent correspondre',
                'first_options' => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Confirmation'),
            ))
            ->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // password encoding is handle by PasswordEncoderSubscriber
                $trainee->setPassword(null);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'trainee' => $trainee);
    }

    /**
     * @param Request         $request
     * @param AbstractTrainee $trainee
     *
     * @Route("/{id}/changeorg", name="trainee.changeorg", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="trainee", permissions="EDIT")
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     *
     * @return mixed
     */
    public function changeOrganizationAction(Request $request, AbstractTrainee $trainee)
    {
        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainee.all.update')) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(ChangeOrganizationType::class, $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('trainee.view', ['id' => $trainee->getId()]);
            }
        }

        return array('form' => $form->createView(), 'trainee' => $trainee);
    }

    /**
     * @param AbstractTrainee $trainee
     *
     * @Route("/{id}/remove", name="trainee.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @SecureParam(name="trainee", permissions="DELETE")
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function deleteAction(AbstractTrainee $trainee)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($trainee);
        $em->flush();
        $this->get('fos_elastica.index')->refresh();

        return array();
    }
}
