<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use danog\MadelineProto\SettingsAbstract;
use ReflectionClass;

/**
 * Array caching trait.
 */
abstract class DriverArray implements DbArray
{
    use ArrayCacheTrait;

    public function __destruct()
    {
        $this->stopCacheCleanupLoop();
    }

    /**
     * Get string representation of driver/table.
     *
     * @return string
     */
    abstract public function __toString(): string;

    public function __wakeup()
    {
        if (isset($this->settings) && \is_array($this->settings)) {
            $clazz = (new ReflectionClass($this))->getProperty('dbSettings')->getType()->getName();
            /**
             * @var SettingsAbstract
             * @psalm-suppress UndefinedThisPropertyAssignment
             */
            $this->dbSettings = new $clazz;
            $this->dbSettings->mergeArray($this->settings);
            unset($this->settings);
        }
    }
    public function offsetExists($index): bool
    {
        throw new \RuntimeException('Native isset not support promises. Use isset method');
    }

    abstract public function initConnection(\danog\MadelineProto\Settings\Database\DatabaseAbstract $settings): \Generator;
    abstract public function initStartup(): \Generator;

    /**
     * @param self $new
     * @param DbArray|array|null $old
     *
     * @return \Generator
     * @throws \Throwable
     */
    protected static function migrateDataToDb(self $new, $old): \Generator
    {
        if (!empty($old) && !$old instanceof static) {
            Logger::log('Converting database.', Logger::ERROR);

            if ($old instanceof DbArray) {
                $old = yield $old->getArrayCopy();
            } else {
                $old = (array) $old;
            }
            $counter = 0;
            $total = \count($old);
            foreach ($old as $key => $item) {
                $counter++;
                if ($counter % 500 === 0) {
                    yield $new->offsetSet($key, $item);
                    Logger::log("Loading data to table {$new}: $counter/$total", Logger::WARNING);
                } else {
                    $new->offsetSet($key, $item);
                }
            }
            Logger::log('Converting database done.', Logger::ERROR);
        }
    }
}
