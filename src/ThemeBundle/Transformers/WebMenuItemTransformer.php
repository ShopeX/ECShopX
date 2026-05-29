<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace ThemeBundle\Transformers;

use League\Fractal\TransformerAbstract;
use ThemeBundle\Entities\WebMenuItem;

class WebMenuItemTransformer extends TransformerAbstract
{
    /**
     * @param array{item: \ThemeBundle\Entities\WebMenuItem, children: array<int, mixed>} $node
     */
    public function transform(array $node): array
    {
        $item = $node['item'];
        $children = $node['children'] ?? [];

        return [
            'id'          => $item->getId(),
            'parent_id'   => $item->getParentId(),
            'name'        => $item->getName(),
            'image_url'   => $item->getImageUrl(),
            'link_type'   => $item->getLinkType(),
            'link_value'  => $item->getLinkValue(),
            'link_extra'  => $this->decodeLinkExtra($item),
            'sort'        => $item->getSort(),
            'status'      => $item->getStatus(),
            'children'    => array_map([$this, 'transform'], $children),
        ];
    }

    /**
     * @param array{item: \ThemeBundle\Entities\WebMenuItem, children: array<int, mixed>} $node
     */
    public function transformFront(array $node): array
    {
        $item = $node['item'];
        $children = $node['children'] ?? [];

        return [
            'id'         => $item->getId(),
            'name'       => $item->getName(),
            'image_url'  => $item->getImageUrl(),
            'link_type'  => $item->getLinkType(),
            'link_value' => $item->getLinkValue(),
            'link_extra' => $this->decodeLinkExtra($item),
            'sort'       => $item->getSort(),
            'children'   => array_map([$this, 'transformFront'], $children),
        ];
    }

    private function decodeLinkExtra(WebMenuItem $item): array
    {
        $raw = $item->getLinkExtra();
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
