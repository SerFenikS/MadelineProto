<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;

use function Amp\call;

/**
 * Memory database backend.
 */
class MemoryArray extends \ArrayIterator implements DbArray
{
    protected function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    /**
     * Get instance.
     *
     * @param string $table
     * @param mixed  $value
     * @param Memory $settings
     * @return Promise<self>
     */
    public static function getInstance(string $table, $value, $settings): Promise
    {
        return call(static function () use ($value) {
            if ($value instanceof MemoryArray) {
                return $value;
            }
            if ($value instanceof DbArray) {
                Logger::log("Loading database to memory. Please wait.", Logger::WARNING);
                if ($value instanceof DriverArray) {
                    yield from $value->initStartup();
                }
                $value = yield $value->getArrayCopy();
            }
            return new static($value);
        });
    }

    public function offsetExists($offset)
    {
        throw new \RuntimeException('Native isset not support promises. Use isset method');
    }

    public function isset($key): Promise
    {
        return new Success(parent::offsetExists($key));
    }

    public function offsetGet($offset): Promise
    {
        return new Success(parent::offsetExists($offset) ? parent::offsetGet($offset) : null);
    }

    public function offsetUnset($offset): Promise
    {
        return new Success(parent::offsetUnset($offset));
    }

    public function count(): Promise
    {
        return new Success(parent::count());
    }

    public function getArrayCopy(): Promise
    {
        return new Success(parent::getArrayCopy());
    }

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            foreach ($this as $key => $value) {
                yield $emit([$key, $value]);
            }
        });
    }
}
