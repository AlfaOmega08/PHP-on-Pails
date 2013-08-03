<?php

namespace ActiveRecord;

trait Pagination
{
    public function paginate($page = null, array $options = [])
    {
        if ($page === null)
            $page = 0;

        $per_page = $this->per_page($options);

        $offset = $page * $per_page;
        $limit = $per_page;

        $this->current_page = $page;

        return $this->limit($limit)->offset($offset)->all();
    }

    public function per_page(array $options = [])
    {
        if (isset($options['per_page']))
            return $options['per_page'];
        else if (isset($this->per_page))
            return $this->per_page;
        else
            return 10;
    }
}