<?php

declare(strict_types=1);

namespace App\Controllers;

class HealthController extends BaseController
{
    public function check(): never
    {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo 'OK';
        exit;
    }
}
