<?php

return [
    'auto_pay' => [
        'advance_days' => (int) env('BILLING_AUTO_PAY_ADVANCE_DAYS', 1),
    ],
];
