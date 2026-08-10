<?php

namespace App\Domain;

use Exception;

final class CompletionException extends Exception
{
    public function __construct(
        public readonly string $reason,
        public readonly ?OccurrenceState $state = null,
    ) {
        parent::__construct($reason);
    }
}
