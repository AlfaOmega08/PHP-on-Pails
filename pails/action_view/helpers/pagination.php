<?php

function pagination_links($object, array $options = [])
{
    $object = $object[0];

    $pages = ceil($object->model()->count() / $object->per_page());
    if ($pages <= 1)
        return "";

    $result = "<div class='pagination_links'>";

    $p = Request::parameters();
    $current_page = isset($p['page']) ? $p['page'] : 0;

    if ($current_page != 0)
    {
        if (!isset($option['previous_link']) || $option['previous_link'] !== false)
            $result .= "<a href='?page=0' class='pagination_previous'>« Precedente</a>";
    }

    for ($i = 0; $i < $pages; $i++)
    {
        $result .= "<a href='?page=$i' class='pagination_link";
        if ($i == $current_page)
            $result .= " current_pagination_link";
        $result .= "'>";
        $result .= $i + 1 . '</a>';
    }

    if ($current_page + 1 != $pages)
    {
        if (!isset($option['next_link']) || $option['next_link'] !== false)
            $result .= "<a href='?page=" . ($current_page + 1) . "' class='pagination_next'>Successiva »</a>";
    }

    $result .= "</div>";

    return $result;
}