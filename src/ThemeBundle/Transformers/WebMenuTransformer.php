<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Transformers;

use League\Fractal\TransformerAbstract;
use ThemeBundle\Entities\WebMenu;

class WebMenuTransformer extends TransformerAbstract
{
    public function transform(WebMenu $menu): array
    {
        return [
            'id'         => $menu->getId(),
            'name'       => $menu->getName(),
            'key'        => $menu->getKey(),
            'status'     => $menu->getStatus(),
            'created_at' => $menu->getCreatedAt() ? $menu->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updated_at' => $menu->getUpdatedAt() ? $menu->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
