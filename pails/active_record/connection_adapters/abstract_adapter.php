<?php

namespace ActiveRecord\ConnectionAdapters;

abstract class AbstractAdapter
{
    use Quoting, DatabaseStatements, SchemaStatements;
    use DatabaseLimits;
    use QueryCache;
    use ColumnDumper;

    const SIMPLE_INT = '/^\d+$/';

    private $connection;

    function __construct()
    {
        $this->define_callbacks('checkout', 'checkin');
    }

    function adapter_name()
    {
        return 'Abstract';
    }

    function supports_migrations()
	{
        return false;
	}

    # Can this adapter determine the primary key for tables not attached
    # to an Active Record class, such as join tables? Back	} specific, as
    # the abstract adapter always returns +false+.
	function supports_primary_key()
	{
		return false;
	}

    # Does this adapter support using DISTINCT within COUNT? This is +true+
    # for all adapters except sqlite.
	function supports_count_distinct()
	{
		return true;
	}

    # Does this adapter support DDL rollbacks in transactions? That is, would
    # CREATE TABLE or ALTER TABLE get rolled back by a transaction? PostgreSQL,
    # SQL Server, and others support this. MySQL and others do not.
	function supports_ddl_transactions()
	{
		return false;
	}

	function supports_bulk_alter()
	{
		return false;
	}

    # Does this adapter support savepoints? PostgreSQL and MySQL do,
    # SQLite < 3.6.8 does not.
	function supports_savepoints()
	{
		return false;
	}

    # Should primary key values be selected from their corresponding
    # sequence before the insert statement? If true, next_sequence_value
    # is called before each insert to set the record's primary key.
    # This is false for all adapters but Firebird.
	function prefetch_primary_key($table_name = null)
    {
        return false;
	}

    # Does this adapter support index sort order()
	function supports_index_sort_order()
	{
		return false;
	}

    # Does this adapter support partial indices()
	function supports_partial_index()
	{
		return false;
	}

    # Does this adapter support explain? As of this writing sqlite3,
    # mysql2, and postgresql are the only ones that do.
	function supports_explain()
	{
		return false;
	}

    # Does this adapter support setting the isolation level for a transaction()
	function supports_transaction_isolation()
	{
		return false;
	}

    # Does this adapter support database extensions? As of this writing only
    # postgresql does.
	function supports_extensions()
	{
		return false;
	}

    # A list of extensions, to be filled in by adapters that support them. At
    # the moment only postgresql does.
	function extensions()
    {
        return [];
	}

    # A list of index algorithms, to be filled by adapters that support them.
    # MySQL and PostgreSQL have support for them right now.
	function index_algorithms()
    {
        return null;
	}

    function disable_referential_integrity($block)
    {
        $block();
    }

    function is_active()
    {
        return null;
    }

    function reconnect()
    {
        $this->clear_cache();
        $this->reset_transition();
    }

    function disconnect()
    {
        $this->clear_cache();
        $this->reset_transition();
    }

    function reset()
    {

    }

    function requires_reloading()
    {
        return false;
    }

    function verify()
    {
        if (!$this->is_active())
            $this->reconnect();
    }

    function raw_connection()
    {
        return $this->connection;
    }

    function open_transactions()
    {
        return $this->transaction->number();
    }

    function create_savepoint()
    {

    }

    function rollback_to_savepoint()
    {

    }

    function release_savepoint()
    {

    }

    function current_savepoint_name()
    {
        return "active_record_" . $this->open_transactions();
    }
}
