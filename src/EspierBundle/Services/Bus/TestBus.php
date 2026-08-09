<?php
/**
 * Test double for Bus: captures post() payload for assertions.
 * Used by ItemsCategoryFrontDisplayTest (TC5/TC6) to assert filter contains is_show_front.
 */

namespace EspierBundle\Services\Bus;

use EspierBundle\Interfaces\ServiceBusInterface;

class TestBus implements ServiceBusInterface
{
    /** @var array|null last $data passed to post() */
    public static $lastPostData = null;

    /** @var array<int, array> history of $data passed to post() */
    public static $postHistory = [];

    /** @var array<int, array> queue of return values for post() */
    public static $postReturnQueue = [];

    public function version($version)
    {
        return $this;
    }

    public function setServiceName($serviceName)
    {
    }

    public function setBaseUrl($url)
    {
    }

    public function json($method, $uri, array $data = [], array $headers = [])
    {
        return [];
    }

    public function get($uri, array $headers = [])
    {
        return [];
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        self::$lastPostData = $data;
        self::$postHistory[] = $data;

        if (!empty(self::$postReturnQueue)) {
            return array_shift(self::$postReturnQueue);
        }

        return [];
    }

    public function put($uri, array $data = [], array $headers = [])
    {
        return [];
    }

    public function patch($uri, array $data = [], array $headers = [])
    {
        return [];
    }

    public function delete($uri, array $data = [], array $headers = [])
    {
        return [];
    }

    public static function getLastPostData(): ?array
    {
        return self::$lastPostData;
    }

    public static function getPostCount(): int
    {
        return count(self::$postHistory);
    }

    public static function getPostHistory(): array
    {
        return self::$postHistory;
    }

    public static function setPostReturnQueue(array $returns): void
    {
        self::$postReturnQueue = $returns;
    }

    public static function reset(): void
    {
        self::$lastPostData = null;
        self::$postHistory = [];
        self::$postReturnQueue = [];
    }

    public static function resetLastPostData(): void
    {
        self::reset();
    }
}
