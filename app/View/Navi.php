<?php

namespace App\View;

class Navi
{
    /**
     * @param  string  $location
     */
    public static function getMenu($location): array
    {
        $locations = get_nav_menu_locations();

        if (! isset($locations[$location])) {
            return [];
        }

        $menu_obj = get_term($locations[$location], 'nav_menu');

        if (! $menu_obj || is_wp_error($menu_obj)) {
            return [];
        }

        $items = wp_get_nav_menu_items($menu_obj->term_id);

        if (! $items) {
            return [];
        }

        return self::buildTree($items);
    }

    /**
     * @param  int  $parentId
     */
    private static function buildTree(array $elements, $parentId = 0): array
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element->menu_item_parent == $parentId) {
                $children = self::buildTree($elements, $element->ID);

                $item = (object) [
                    'id' => $element->ID,
                    'label' => $element->title,
                    'url' => $element->url,
                    'target' => $element->target,
                    'active' => $element->current || $element->current_item_ancestor,
                    'classes' => implode(' ', $element->classes),
                    'children' => $children,
                ];

                $branch[] = $item;
            }
        }

        return $branch;
    }
}
