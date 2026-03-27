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

    public static function resetLastPostData(): void
    {
        self::$lastPostData = null;
    }
}
