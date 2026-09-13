<?php

return [
    // TrustProxies reads this after configuration loads, including config cache.
    // Accept a comma-separated list of proxy IP addresses or CIDR ranges.
    'proxies' => env('TRUSTED_PROXIES', []),
];
