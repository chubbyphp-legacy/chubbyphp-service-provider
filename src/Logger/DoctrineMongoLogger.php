<?php

declare(strict_types=1);

/*
 * (c) Kris Wallsmith <kris@symfony.com> (https://github.com/doctrine/DoctrineMongoDBBundle/)
 */

namespace Chubbyphp\ServiceProvider\Logger;

use Psr\Log\LoggerInterface;

final class DoctrineMongoLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $batchInsertThreshold;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param LoggerInterface $logger
     * @param int             $batchInsertThreshold
     * @param string          $prefix
     */
    public function __construct(LoggerInterface $logger, int $batchInsertThreshold, string $prefix)
    {
        $this->logger = $logger;
        $this->batchInsertThreshold = $batchInsertThreshold;
        $this->prefix = $prefix;
    }

    /**
     * @param array $query
     */
    public function logQuery(array $query)
    {
        if (isset($query['batchInsert'])
            && null !== $this->batchInsertThreshold && $this->batchInsertThreshold <= $query['num']) {
            $query['data'] = '**'.$query['num'].' item(s)**';
        }

        array_walk_recursive($query, function (&$value) {
            $value = $this->flatValue($value);
        });

        $this->logger->debug($this->prefix.json_encode($query));
    }

    /**
     * @param mixed $value
     *
     * @return mixed string
     */
    private function flatValue($value)
    {
        if ($value instanceof \MongoBinData) {
            return base64_encode($value->bin);
        }

        if (is_float($value) && is_infinite($value)) {
            return ($value < 0 ? '-' : '').'Infinity';
        }

        return $value;
    }
}
