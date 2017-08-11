<?php

namespace Sygefor\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Tests\Encoder\PasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StrongPasswordValidator extends ConstraintValidator
{
    private $tokenStorage;
    private $encoderFactory;
    private $hackedPasswordUrl;

    public function __construct(TokenStorageInterface $tokenStorage, EncoderFactoryInterface $encoderFactory, $hackedPasswordUrl)
    {
        $this->tokenStorage = $tokenStorage;
        $this->encoderFactory = $encoderFactory;
        $this->hackedPasswordUrl = $hackedPasswordUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($password, Constraint $constraint)
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\StrongPassword');
        }

        $user = $constraint->user ? $constraint->user : $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new ConstraintDefinitionException('The User object must implement the UserInterface interface.');
        }

        if (strlen($password) < $constraint->minLength) {
            $this->context->buildViolation($constraint->shortMessage)
                ->setParameter('%minLength%', $constraint->minLength)
                ->setCode(StrongPassword::TOO_SHORT)
                ->addViolation();

            return;
        }

        /** @var PasswordEncoder $passwordEncoder */
        $passwordEncoder = $this->encoderFactory->getEncoder($user);
        $maxPasswordLength = $passwordEncoder ? $passwordEncoder::MAX_PASSWORD_LENGTH : $constraint->maxLength;
        if (strlen($password) > $maxPasswordLength) {
            $this->context->buildViolation($constraint->longMessage)
                ->setParameter('%maxLength%', $maxPasswordLength)
                ->setCode(StrongPassword::TOO_LONG)
                ->addViolation();

            return;
        }

        $results = @file_get_contents($this->hackedPasswordUrl.sha1($password));
        if ($results !== false) {
            $hacked = json_decode($results);
            if ($hacked) {
                $this->context->buildViolation($constraint->hackedMessage)
                    ->setCode(StrongPassword::HACKED)
                    ->addViolation();

                return;
            }
        }
    }
}
