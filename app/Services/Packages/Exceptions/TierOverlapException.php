<?php

namespace App\Services\Packages\Exceptions;

use Exception;

class TierOverlapException extends Exception
{
    public function __construct(
        public int $tierAFrom,
        public int $tierATo,
        public int $tierBFrom,
        public int $tierBTo,
        string $message = ''
    ) {
        if (empty($message)) {
            $message = "Existe una superposición de rangos de invitados entre el tramo [{$tierAFrom} - {$tierATo}] y el tramo [{$tierBFrom} - {$tierBTo}].";
        }

        parent::__construct($message);
    }
}
