<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Consent Text for Ley N° 29733 (Personal Data Protection)
    |--------------------------------------------------------------------------
    */
    'consent_text_law_29733' => env(
        'SHIPIBO_CONSENT_TEXT_LAW_29733',
        'Autorizo expresamente a MYTEMS E.I.R.L. (Shipibo Coctelería) a utilizar mi nombre y teléfono exclusivamente para contactarme y coordinar la cotización de este evento, conforme a la Ley N° 29733 de Protección de Datos Personales. Mis datos no serán transferidos a terceros.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Schedule Percentages for Contract Formalization
    |--------------------------------------------------------------------------
    | Cuota 1: Adelanto Inicial / Reserva (Defecto: 20%)
    | Cuota 2: Saldo Restante antes del evento (Defecto: 80%)
    */
    'default_deposit_percentage' => (float) env('SHIPIBO_DEFAULT_DEPOSIT_PERCENTAGE', 20.0),
    'default_balance_percentage' => (float) env('SHIPIBO_DEFAULT_BALANCE_PERCENTAGE', 80.0),

    /*
    |--------------------------------------------------------------------------
    | PDR Rule 6 Validation Threshold
    |--------------------------------------------------------------------------
    | Tolerancia máxima para diferencias por redondeo de centavos
    */
    'rule_6_tolerance' => 0.01,
];
