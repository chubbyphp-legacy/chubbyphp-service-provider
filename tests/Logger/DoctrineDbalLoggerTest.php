<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\Logger\DoctrineDbalLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\ServiceProvider\Logger\DoctrineDbalLogger
 */
class DoctrineDbalLoggerTest extends TestCase
{
    public function testStartQuery()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'select * from users where username = :username',
                [
                    'username' => 'john.doe+66666666666666666 [...]',
                    'picture' => '(binary value)',
                    'active' => true,
                ]
            );

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
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(self::never())->method('debug');

        $dbalLogger = new DoctrineDbalLogger($logger);
        $dbalLogger->stopQuery();
    }
}
