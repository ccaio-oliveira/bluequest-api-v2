<?php

namespace App\Domain;

use Exception;

final class ChallengeException extends Exception
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
