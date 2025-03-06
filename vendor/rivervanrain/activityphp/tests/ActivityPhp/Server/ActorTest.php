<?php

namespace ActivityPhpTest\Server;

use ActivityPhp\Server;
use PHPUnit\Framework\TestCase;

/*
 * These scenarios are around server side actor instance
 */
class ActorTest extends TestCase
{
    /**
     * Check that an actor has no public key
     */
    public function testActorWithNoPublicKeyPem()
    {
        $server = new Server([
            'instance' => [
                'host'  => 'localhost',
                'port'  => 8000,
                'debug' => true,
                'actorPath' => '/accounts/<handle>',
            ],
            'logger'    => [
               'driver' => '\Psr\Log\NullLogger'
            ],
            'cache' => [
                'enabled' => false,
            ]
        ]);

        $actor = $server->actor('bob@localhost:8000');

        // Assert no public is set
        $this->assertEquals(
            false,
            $actor->getPublicKeyPem()
        );
    }
}
