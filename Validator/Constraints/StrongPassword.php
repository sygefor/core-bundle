<?php

namespace Sygefor\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Validator\Constraint;

class StrongPassword extends Constraint
{
    const TOO_SHORT = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1999';
    const TOO_LONG = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1998';
    const HACKED = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1997';

    protected static $errorNames = array(
        self::TOO_SHORT => 'TOO_SHORT_ERROR',
        self::TOO_LONG => 'TOO_LONG_ERROR',
        self::HACKED => 'HACKED_ERROR',
    );

    public $user = null;

    public $minLength = 8;
    public $shortMessage = 'The password must contains at least %minLength% characters';

    public $maxLength = BasePasswordEncoder::MAX_PASSWORD_LENGTH;
    public $longMessage = 'The password must contains at maximum %maxLength% characters';

    public $hackedMessage = 'This password has already been hacked on another website. Please change it.';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
