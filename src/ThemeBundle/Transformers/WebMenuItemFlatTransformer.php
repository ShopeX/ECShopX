<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Transformers;

use League\Fractal\TransformerAbstract;
use ThemeBundle\Entities\WebMenuItem;

class WebMenuItemFlatTransformer extends TransformerAbstract
{
    public function transform(WebMenuItem $item): array
    {
        $decodedExtra = json_decode((string) $item->getLinkExtra(), true);

        return [
            'id'          => $item->getId(),
            'menu_id'     => $item->getMenuId(),
            'parent_id'   => $item->getParentId(),
            'name'        => $item->getName(),
            'image_url'   => $item->getImageUrl(),
            'link_type'   => $item->getLinkType(),
            'link_value'  => $item->getLinkValue(),
            'link_extra'  => is_array($decodedExtra) ? $decodedExtra : [],
            'sort'        => $item->getSort(),
            'status'      => $item->getStatus(),
            'created_at'  => $item->getCreatedAt() ? $item->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updated_at'  => $item->getUpdatedAt() ? $item->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
