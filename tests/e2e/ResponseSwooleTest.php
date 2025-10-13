<?php

namespace Utopia\Http\Tests;

use PHPUnit\Framework\TestCase;
use Tests\E2E\Client;

/**
 * @group swoole
 * @group e2e
 */
class ResponseSwooleTest extends TestCase
{
    use BaseTest;
    protected Client $client;

    public function setUp(): void
    {
        $this->client = new Client('http://swoole');
    }
}
