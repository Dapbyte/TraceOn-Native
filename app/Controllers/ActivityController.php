<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

class ActivityController extends BaseController
{
    private function stub(): never
    {
        Response::error('NOT_IMPLEMENTED', 'Belum diimplementasikan', 501);
    }

    public function __call(string $name, array $args): never
    {
        $this->stub();
    }
}
