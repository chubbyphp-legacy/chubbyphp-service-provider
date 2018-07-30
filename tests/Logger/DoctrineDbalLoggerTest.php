<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\ServiceProvider\Logger\DoctrineDbalLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\ServiceProvider\Logger\DoctrineDbalLogger
 */
class DoctrineDbalLoggerTest extends TestCase
{
    use MockByCallsTrait;

    public function testStartQuery()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockByCalls(LoggerInterface::class, [
            Call::create('debug')
                ->with(
                    'select * from users where username = :username',
                    [
                        'username' => 'john.doe+66666666666666666 [...]',
                        'picture' => '(binary value)',
                        'active' => true,
                    ]
                ),
        ]);

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->startQuery(
            'select * from users where username = :username',
            [
                'username' => 'john.doe+6666666666666666666@gmail.com',
                'picture' => base64_decode('R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs='),
                'active' => true,
            ]
        );
    }

    public function testStopQuery()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockByCalls(LoggerInterface::class);

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->stopQuery();
    }
}
