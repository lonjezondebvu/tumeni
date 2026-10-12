<?php
return [
 'enabled'=>(bool)env('PAYCHANGU_ENABLED',false),
 'secret_key'=>env('PAYCHANGU_SECRET_KEY'),
 'webhook_secret'=>env('PAYCHANGU_WEBHOOK_SECRET'),
 'mode'=>env('PAYCHANGU_MODE','test'),
 'public_url'=>env('PAYCHANGU_PUBLIC_URL'),
 'minimum_minor'=>(int)env('PAYCHANGU_MINIMUM_TOPUP_MINOR',10000),
 'maximum_minor'=>(int)env('PAYCHANGU_MAXIMUM_TOPUP_MINOR',100000000),
 'driver_trial_credit_minor'=>(int)env('DRIVER_TRIAL_CREDIT_MINOR',450000),
];
