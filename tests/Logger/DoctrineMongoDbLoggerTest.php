<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\Logger\DoctrineMongoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\ServiceProvider\Logger\DoctrineMongoLogger
 */
class DoctrineMongoDbLoggerTest extends TestCase
{
    public function testLogQuerySingle()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(self::once())
            ->method('debug')
            ->with('Alternative prefix: {"data":[{"binary":"YWJjZGVmZ2g=","posInfinite":"Infinity","negInfinite":"-Infinity"}]}');

        /** @var \MongoBinData $binary */
        $binary = $this->getMockBuilder(\MongoBinData::class)->disableOriginalConstructor()->getMock();
        $binary->bin = 'abcdefgh';

        $doctrineLogger = new DoctrineMongoLogger($logger, 2, 'Alternative prefix: ');
        $doctrineLogger->logQuery([
            'data' => [
                ['binary' => $binary, 'posInfinite' => INF, 'negInfinite' => -INF],
            ],
        ]);
    }

    public function testLogQueryWithSmallBatch()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(self::once())
            ->method('debug')
            ->with('Alternative prefix: {"batchInsert":true,"num":1,"data":[{"key":"value"}]}');

        $doctrineLogger = new DoctrineMongoLogger($logger, 2, 'Alternative prefix: ');
        $doctrineLogger->logQuery([
            'batchInsert' => true,
            'num' => 1,
            'data' => [
                ['key' => 'value'],
            ],
        ]);
    }

    public function testLogQueryWithLargeBatch()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(self::once())
            ->method('debug')
            ->with('Alternative prefix: {"batchInsert":true,"num":2,"data":"**2 item(s)**"}');

        $doctrineLogger = new DoctrineMongoLogger($logger, 1, 'Alternative prefix: ');
        $doctrineLogger->logQuery([
            'batchInsert' => true,
            'num' => 2,
            'data' => [
                ['key' => 'value'],
                ['key' => 'value'],
            ],
        ]);
    }
}
