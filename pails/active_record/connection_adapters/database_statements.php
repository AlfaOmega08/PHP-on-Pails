<?php

namespace ActiveRecord\ConnectionAdapters;

trait DatabaseStatements
{
    private $transaction;

    function select_all($sql, $name = null)
    {
        return $this->select($sql, $name);
    }

    function select_one($sql, $name = null)
    {
        $result = $this->select_all($sql, $name);
        if (count($result))
            return $result[0];
        return null;
    }

    function select_value($sql, $name = null)
    {
        if ($result = $this->select_one($sql, $name))
            return array_shift($result);
        return null;
    }

    function select_values($sql, $name = null)
    {
        $result = $this->select_rows($sql, $name);
        return array_map(function($v) { return $v[0]; }, $result);
    }

    abstract function select_rows($sql, $name = null);

    abstract function execute($sql, $name = null);

    function exec_query()
    {
        return null;
    }

    function exec_insert($sql, $name, $binds, $pk = null, $sequence_name = null)
    {
        return $this->exec_query($sql, $name, $binds);
    }

    function exec_delete($sql, $name, $binds)
    {
        $this->exec_query($sql, $name, $binds);
    }

    function exec_update($sql, $name, $binds)
    {
        $this->exec_query($sql, $name, $binds);
    }

    function insert($sql, $name = null, $pk = null, $id_value = null, $sequence_name = null, $binds = [])
    {
        $value = $this->exec_insert($sql, $name, $binds, $pk, $sequence_name);
        if ($id_value)
            return $id_value;
        return $this->last_inserted_id($value);
    }

    # Executes the update statement and returns the number of rows affected.
    function update($sql, $name = null, $binds = [])
    {
        $this->exec_update($sql, $name, $binds);
    }

    # Executes the delete statement and returns the number of rows affected.
    function delete($sql, $name = null, $binds = [])
    {
        $this->exec_delete($sql, $name, $binds);
    }

    # Returns +true+ when the connection adapter supports prepared statement
    # caching, otherwise returns +false+
    function supports_statement_cache()
    {
        return false;
    }

    function transaction(array $options, callable $block)
    {
        try
        {
            if (!$options['requires_new'] && $this->current_transaction()->joinable())
            {
                if (isset($options['isolation']))
                    throw new \ActiveRecord\TransactionIsolationError("cannot set isolation when joining a transaction");
                $block();
            }
            else
                $this->within_new_transaction($options, $block);
        }
        catch (\ActiveRecord\Rollback $e)
        {

        }
    }

    function within_new_transaction($options, callable $block)
    {
        $transaction = $this->begin_transaction($options);
        try
        {
            $block();
            $this->commit_transaction();
        }
        catch (\Exception $error)
        {
            if ($transaction)
                $this->rollback_transaction();
            throw;
        }
    }

    function current_transaction()
    {
        if (is_null($this->transaction))
            $this->reset_transaction();
        return $this->transaction;
    }

    function transaction_open()
    {
        return $this->current_transaction()->is_open();
    }

    function begin_transaction($options)
    {
        $this->transaction = $this->current_transaction()->begin($options);
    }

    function commit_transaction()
    {
        $this->transaction = $this->current_transaction()->commit();
    }

    function rollback_transaction()
    {
        $this->transaction = $this->current_transaction()->rollback();
    }

    function reset_transaction()
    {
        $this->transaction = new ClosedTransaction($this);
    }

      # Register a record with the current transaction so that its after_commit and after_rollback callbacks
      # can be called.
    function add_transaction_record($record)
    {
        $this->current_transaction()->add_record($record);
    }

    function begin_db_transaction()
    {
    }
}
