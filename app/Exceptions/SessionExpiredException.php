<?php

namespace App\Exceptions;

use Exception;

class SessionExpiredException extends Exception
{
    protected $message = 'Session has expired due to inactivity.';
    protected $code = 401;
}