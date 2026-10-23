<?php

namespace App\Services\Packages\Exceptions;

use Exception;

class GuestOutOfRangeException extends Exception
{
    public function __construct(
        public int $guests,
        public int $minGuests,
        public ?int $maxGuests = null,
        string $message = ''
    ) {
        if (empty($message)) {
            $rangeDesc = $maxGuests ? "entre {$minGuests} y {$maxGuests}" : "mínimo {$minGuests}";
            $message = "La cantidad de invitados solicitada ({$guests}) está fuera del rango permitido ({$rangeDesc}) para este paquete.";
        }

        parent::__construct($message);
    }
}
