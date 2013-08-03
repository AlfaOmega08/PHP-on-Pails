<?php

namespace ActiveRecord\ConnectionAdapters;

trait DatabaseLimits
{
    # Returns the maximum length of a table alias.
    function table_alias_length()
    {
        return 255;
    }

    # Returns the maximum length of a column name.
    function column_name_length()
    {
        return 64;
    }

    # Returns the maximum length of a table name.
    function table_name_length()
    {
        return 64;
    }

    # Returns the maximum allowed length for an index name. This
    # limit is enforced by rails and Is less than or equal to
    # <tt>index_name_length</tt>. The gap between
    # <tt>index_name_length</tt> is to allow internal rails
    # operations to use prefixes in temporary operations.
    function allowed_index_name_length()
    {
        return $this->index_name_length();
    }

    # Returns the maximum length of an index name.
    function index_name_length()
    {
        return 64;
    }

    # Returns the maximum number of columns per table.
    function columns_per_table()
    {
        return 1024;
    }

    # Returns the maximum number of indexes per table.
    function indexes_per_table()
    {
        return 16;
    }

    # Returns the maximum number of columns in a multicolumn index.
    function columns_per_multicolumn_index()
    {
        return 16;
    }

    # Returns the maximum number of elements in an IN (x,y,z) clause.
    # nil means no limit.
    function in_clause_length()
    {
        return null;
    }

    # Returns the maximum length of an SQL query.
    function sql_query_length()
    {
        return 1048575;
    }

    # Returns maximum number of joins in a single query.
    function joins_per_query()
    {
        return 256;
    }
}