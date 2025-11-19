<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Data;

class MapData
{
    /**
     * 纬度
     * @var string
     */
    protected $lng;

    /**
     * 经度
     * @var string
     */
    protected $lat;

    /**
     * 地址
     * @var string
     */
    protected $address;

    /**
     * 地址部件
     * @var array
     */
    protected $addressComponent;

    /**
     * @return array
     */
    public function getAddressComponent(): ?array
    {
        return $this->addressComponent;
    }

    /**
     * @param array $addressComponent
     */
    public function setAddressComponent(?array $addressComponent): void
    {
        $this->addressComponent = $addressComponent;
    }

    /**
     * @return string
     */
    public function getLng(): string
    {
        return (string)$this->lng;
    }

    /**
     * @param string $lng
     */
    public function setLng(?string $lng): void
    {
        $this->lng = $lng;
    }

    /**
     * @return string
     */
    public function getLat(): string
    {
        return (string)$this->lat;
    }

    /**
     * @param string $lat
     */
    public function setLat(?string $lat): void
    {
        $this->lat = $lat;
    }

    /**
     * @return string
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }
}
