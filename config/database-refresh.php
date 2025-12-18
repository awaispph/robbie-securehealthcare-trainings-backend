<?php

return [
    'enabled' => env('SHOULD_REFRESH_DATABASE', false),
    'hours' => env('REFRESH_DATABASE_HOURS', null),
];
