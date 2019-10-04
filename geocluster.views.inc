<?php

/**
 * @file
 * Hooks for Views integration.
 */


/**
 * Adds custom field handlers for clustered data.
 *
 * Implements hook_views_data_alter().
 */
function geocluster_views_data_alter(&$data) {

  $field_type = 'geofield';
  $field_map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType($field_type);
  
  foreach ($field_map as $entity_type_id => $fields) {
    foreach ($fields as $field_name => $field)
    {
      $table_name = $entity_type_id.'__' . $field_name;

      
      // We could add the field name as prefix but we don't for handler simplicity.
      $geocluster_field = 'geocluster';
      $data[$table_name][$geocluster_field . '_ids'] = array(
        'group' => 'Content',
        'title' => 'Geocluster ids (' . $field_name . ')',
        'help' => 'Adds Geocluster ids from a clustered result',
        'field' => array(
          'table' => $table_name,
          'id' => 'geocluster_handler_field',
        ),
      );
      $data[$table_name][$geocluster_field . '_count'] = array(
        'group' => 'Content',
        'title' => 'Geocluster result count (' . $field_name . ')',
        'help' => 'Adds Geocluster result count from a clustered result',
        'field' => array(
          'table' => $table_name,
          'id' => 'geocluster_handler_field',
        ),
      );
      $data[$table_name][$geocluster_field . '_lat'] = array(
        'real field' => $field_name . '_lat',
        'group' => 'Content',
        'title' => 'Geocluster lat (' . $field_name . ')',
        'help' => 'Adds Geocluster lat from a clustered result',
        'field' => array(
          'table' => $table_name,
          'id' => 'geocluster_handler_field',
        ),
      );
      $data[$table_name][$geocluster_field . '_lon'] = array(
        'real field' => $field_name . '_lon',
        'group' => 'Content',
        'title' => 'Geocluster lon (' . $field_name . ')',
        'help' => 'Adds Geocluster lon from a clustered result',
        'field' => array(
          'table' => $table_name,
          'id' => 'geocluster_handler_field',
        ),
      );
    }
  }
}