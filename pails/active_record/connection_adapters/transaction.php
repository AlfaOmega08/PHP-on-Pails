<?php

namespace ActiveRecord\ConnectionAdapters;

class Transaction
{
    public $connection;
    protected $state;

    function __construct($connection)
    {
        $this->connection = $connection;
        $this->state = new TransactionState;
    }

    function state()
    {
        return $this->state;
    }
}

class TransactionState
{
    public $parent;
    private $state;

    private $VALID_STATES = [ 'committed', 'rolledback', null ];

    function __construct($state = null)
    {
        $this->state = $state;
    }

    function is_committed()
    {
        return $this->state == 'committed';
    }

    function is_rolledback()
    {
        return $this->state == 'rolledback';
    }

    function set_state($state)
    {
        if (!in_array($state, $this->VALID_STATES))
            throw new \ArgumentException("Invalid transaction state {$state}");

        $this->state = $state;
    }
}

class ClosedTransaction extends Transaction
{
    function number()
    {
        return 0;
    }

    function begin($options = [])
    {
        return new RealTransaction($this->connection, $this, $options);
    }

    function is_closed()
    {
        return true;
    }

    function is_open()
    {
        return false;
    }

    function is_joinable()
    {
        return false;
    }

    function add_record($record)
    {
    }
}

class OpenTransaction extends Transaction
{
    public $parent, $records;
    public $joinable;

    function __construct($connection, $parent, $options = [])
    {
        parent::__construct($connection);

        $this->parent = $parent;
        $this->records = [];
        $this->finishing = false;
        $this->joinable = isset($options['joinable']) ? $options['joinable'] : true;
    }

    # This state is necessary so that we correctly handle stuff that might
    # happen in a commit/rollback. But it's kinda distasteful. Maybe we can
    # find a better way to structure it in the future.
    function is_finishing()
    {
        return $this->finishing;
    }

    function is_joinable()
    {
        return $this->joinable && !$this->is_finishing();
    }

    function number()
    {
        if ($this->is_finishing())
            return $this->parent->number();
        else
            return $this->parent->number() + 1;
    }

    function begin($options = [])
    {
        if ($this->is_finishing())
            return $this->parent->begin();
        else
            return new SavepointTransaction($this->connection, $this, $options);
    }

    function rollback()
    {
        $this->finishing = true;
        $this->perform_rollback();
        return $this->parent;
    }

    function commit()
    {
        $this->finishing = true;
        $this->perform_commit();
        return $this->parent;
    }

    function add_record($record)
    {
        if ($record->has_transactional_callbacks())
            $this->records[] = $record;
        else
            $record->set_transaction_state($this->state);
    }

    function rollback_records()
    {
        $this->state->set_state('rolledback');

        $records = array_unique($this->records);
        foreach ($records as $record)
        {
            try
            {
                $record->rolledback_($this->parent->is_closed());
            }
            catch (\Exception $e)
            {

            }
        }
    }

    function commit_records()
    {
        $this->state->set_state('committed');

        $records = array_unique($this->records);
        foreach ($records as $record)
        {
            try
            {
                $record->committed_();
            }
            catch (\Exception $e)
            {

            }
        }
    }

    function is_closed()
    {
        return false;
    }


    function is_open()
    {
        return true;
    }
}

class RealTransaction extends OpenTransaction
{
    function __construct($connection, $parent, $options = [])
    {
        parent::__construct($connection, $parent, $options);

        if (isset($options['isolation']))
            $connection->begin_isolated_db_transaction($options['isolation']);
        else
            $connection->begin_db_transaction();
    }

    function perform_rollback()
    {
        $this->connection->rollback_db_transaction();
        $this->rollback_records();
    }

    function perform_commit()
    {
        $this->connection->commit_db_transaction();
        $this->commit_records();
    }
}

class SavepointTransaction extends OpenTransaction
{
    function __construct($connection, $parent, $options = [])
    {
        if (isset($options['isolation']))
            throw new \ActiveRecord\TransactionIsolationError("cannot set transaction isolation in a nested transaction");

        parent::__construct($connection, $parent, $options);
        $this->connection->create_savepoint();
    }

    function perform_rollback()
    {
        $this->connection->rollback_to_savepoint();
        $this->rollback_records();
    }

    function perform_commit()
    {
        $this->state->set_state('committed');
        $this->state->parent = $this->parent->state;
        $this->connection->release_savepoint();
    }
}
