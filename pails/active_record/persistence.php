<?php

namespace ActiveRecord;

trait Persistence
{
    public function decrement($attribute, $by = 1)
    {
        if (is_null($this[$attribute]))
            $this[$attribute] = 0;
        $this[$attribute] -= $by;
        return $this;
    }

    public function decrement_($attribute, $by = 1)
    {
        return $this->decrement($attribute, $by)->update_attribute($attribute, $this[$attribute]);
    }

    public function delete()
    {
        if ($this->is_persisted())
            self::delete_by_id($this->id);

        $this->destroyed = true;
        $this->freezed = true;
    }

    public function update_attribute($name, $value)
    {
        if (in_array($name, $this->readonly_attributes))
            throw new ActiveRecordError("{$name} is marked as readonly");

        $this->name = $value;
        $this->save([ 'validate' => false ]);
    }

    public function save(array $options = [])
    {
        try
        {
            return $this->create_or_update($options);
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    public function save_()
    {
        if (!$this->create_or_update())
            throw new RecordNotSaved();
    }

    private function create_or_update()
    {
        if ($this->is_readonly())
            throw new ReadOnlyRecord();

        $result = $this->is_new_record() ? $this->create() : $this->update();
        return $result != false;
    }

    private function create()
    {
        $attributes_values = arel_attributes_values($this->id !== null);

        $new_id = self::unscoped()->insert($attributes_values);

        if (static::primary_key())
        {
            if ($this->id === null)
                $this->id = $new_id;
        }

        $this->new_record = false;
        return $this->id;
    }
}
