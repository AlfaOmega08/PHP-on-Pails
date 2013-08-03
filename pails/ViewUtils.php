<?php

function form_for($record, array $options, callable $form_definition)
{
    return (new ActionView\Helpers\FormHelper)->form_for($record, $options, $form_definition);
}

function form_tag($url, $options = [], callable $block = null)
{
    return \ActionView\Helpers\FormHelper::form_tag($url, $options, $block);
}

function apply_form_for_options($object_or_array, &$options)
{
    $object = is_array($object_or_array) ? end($object_or_array) : $object_or_array;

    $as = isset($options['as']) ? $options['as'] : null;
    list($action, $method) = $object->is_persisted() ? [ 'edit', 'put' ] : [ 'new', 'post' ];

    $new_options = [
    //    'class' => ($as ? "{$action}_{$as}" : dom_class($object, $action)),
      //  'id' => ($as ? "{$action}_{$as}" : implode(' ', [ $options['namespace'], dom_id($object, $action)].compact).presence,
        'method' => $method
    ];

    $options['html'] = array_merge($new_options, $options['html']);

    //$options['url'] ||= polymorphic_path(object_or_array, :format => options.delete(:format));
}

function format_date($date, $format)
{
    $d = new DateTime($date);
    return $d->format($format);
}

function csrf_meta_tag()
{
    return "<meta name='csrf-param' content='authenticity_token' />\n<meta name='csrf-token' content='" . \ActionController\Base::form_authenticity_token() . "' />";
}