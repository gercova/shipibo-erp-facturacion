<?php

namespace App\Exceptions;

use Exception;

class Rule6ViolationException extends Exception
{
    public function __construct(
        public float $sumInstallments,
        public float $contractTotal,
        string $message = ''
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Violación de la Regla 6 del PDR: La suma de las cuotas (S/ %s) no coincide con el total del contrato (S/ %s). Diferencia: S/ %s.',
                number_format($sumInstallments, 2),
                number_format($contractTotal, 2),
                number_format(abs($sumInstallments - $contractTotal), 2)
            );
        }

        parent::__construct($message);
    }
}
