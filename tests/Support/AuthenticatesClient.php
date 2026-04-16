<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuthenticatesClient
{
    private function authenticateClient(KernelBrowser $client): void
    {
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'testuser',
            'password' => 'testpass',
        ]));
    }
}
