<?php

namespace App\Exceptions;

class TelegramAuthException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}
