<?php

return [
    'driver' => env('AUTOMATION_DRIVER', 'playwright'),

    'node_binary' => env('AUTOMATION_NODE_BINARY', 'node'),
    'playwright_script' => env('AUTOMATION_PLAYWRIGHT_SCRIPT', 'automation-worker/run-playwright.js'),

    'headless' => env('AUTOMATION_HEADLESS', true),
    'timeout_seconds' => (int) env('AUTOMATION_TIMEOUT_SECONDS', 180),
    'navigation_timeout_ms' => (int) env('AUTOMATION_NAVIGATION_TIMEOUT_MS', 45000),
    'action_timeout_ms' => (int) env('AUTOMATION_ACTION_TIMEOUT_MS', 6000),
];
