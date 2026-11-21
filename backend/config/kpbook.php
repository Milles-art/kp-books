<?php

return [
    // When true, the simulated gateway can confirm payments (demo and local only).
    'simulate_payments' => env('KPBOOK_SIMULATE_PAYMENTS', false),
    'default_gateway' => env('KPBOOK_GATEWAY', 'simulated'),
    'currency' => 'TZS',
];
