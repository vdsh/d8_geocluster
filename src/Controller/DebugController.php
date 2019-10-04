<?php

namespace Drupal\geocluster\Controller;

use Drupal\Core\Controller\ControllerBase;



/**
 * Defines DebugController class.
 */
class DebugController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
  
   $resolutions = \Drupal\geocluster\Utility\GeoclusterHelper::resolutions();
    var_dump($resolutions);
    
  //  $config = new \Drupal\geocluster\Plugin\GeoclusterAlgorithm\MySQLGeohashGeoclusterAlgorithm();
    
/*
    $type = \Drupal::service('plugin.manager.geocluster_test');
    $plugin_definitions = $type->getDefinitions();
    var_dump($plugin_definitions);*/
    
    exit();
    
  /*
    // Enable geocluster views plugin.
   
   $config = \Drupal::service('config.factory')->getEditable('views.settings');
    $display_extenders = $config->get('display_extenders') ?: array();
    $display_extenders[] = 'geocluster_views_display_extender';
    $config->set('display_extenders', $display_extenders);
    $config->save();
  
  
    // Disable geocluster views plugin.
  
  $config = \Drupal::service('config.factory')->getEditable('views.settings');
  $display_extenders = $config->get('display_extenders') ?: array();
  $key = array_search('geocluster_views_display_extender', $display_extenders);
  if ($key!== FALSE) {
    unset($display_extenders[$key]);
    $config->set('display_extenders', $display_extenders);
    $config->save();  exit('ici');
  }
  exit('la');*/
  
  
    /*
    $field_type= 'geofield';
    
    for ($i = 1; $i <= 12; $i++) {
      $name = 'geocluster_index_' . $i;
      field_definition_add_helper($field_type, $name);
      //field_definition_delete_helper($field_type, $name);
    }
  */
    return [
      '#type' => 'markup',
      '#markup' => 'hello',
    ];
    
  }

}

/**
 * Add a new column for fieldType
 * @param string $field_type
 * @param sring $new_property
 */
 // from https://gist.github.com/JPustkuchen/ce53d40303a51ca5f17ce7f48c363b9b
function field_definition_add_helper($field_type, $new_property) {
  $manager   = \Drupal::entityDefinitionUpdateManager();
  $field_map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType($field_type);
  
  foreach ($field_map as $entity_type_id => $fields) {
    
    foreach (array_keys($fields) as $field_name) {
      $field_storage_definition = $manager->getFieldStorageDefinition($field_name, $entity_type_id);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      
      if ($storage instanceof \Drupal\Core\Entity\Sql\SqlContentEntityStorage) {
        $table_mapping = $storage->getTableMapping([
          $field_name => $field_storage_definition,
        ]);
        $table_names = $table_mapping->getDedicatedTableNames();
        $columns = $table_mapping->getColumnNames($field_name);
        
        foreach ($table_names as $table_name) {
          $field_schema = $field_storage_definition->getSchema();
          $schema = \Drupal::database()->schema();
          $field_exists = $schema->fieldExists($table_name, $columns[$new_property]);
          $table_exists = $schema->tableExists($table_name);
          $index_schema = drupal_get_module_schema('geocluster', $table_name);
          if (!$field_exists && $table_exists) {
            $schema->addField($table_name, $columns[$new_property], $field_schema['columns'][$new_property]);
            // TODO: add those columns as Index
          }
        }
      }
      //$manager->updateFieldStorageDefinition($field_storage_definition);
    }
  }
  
}

/**
 * Remove a column of field_type
 * @param string $field_type FieldTypeId in your definition
 * @param string $property column name
 */
function field_definition_delete_helper($field_type, $property) {
  $field_map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType($field_type);
  foreach ($field_map as $entity_type_id => $fields) {
    foreach (array_keys($fields) as $field_name) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      _field_property_definition_delete($entity_type_id, $field_name, $property);
    }
  }
  
}

/**
 * Inner function, called by field_definition_delete_helper
 * @param string $entity_type_id
 * @param string $field_name
 * @param string $property
 */
function _field_property_definition_delete($entity_type_id, $field_name, $property) {
  $entity_type_manager  = \Drupal::entityTypeManager();
  $entity_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_storage_schema_sql    = \Drupal::keyValue('entity.storage_schema.sql');
  $entity_definitions_installed = \Drupal::keyValue('entity.definitions.installed');
  
  $entity_type = $entity_type_manager->getDefinition($entity_type_id);
  //$field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
  $field_storage_definition = $entity_update_manager->getFieldStorageDefinition($field_name, $entity_type_id);
  $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
  /** @var Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $entity_storage->getTableMapping([
    $field_name => $field_storage_definition,
  ]);
  
  // Load the installed field schema so that it can be updated.
  $schema_key = "$entity_type_id.field_schema_data.$field_name";
  $field_schema_data = $entity_storage_schema_sql->get($schema_key);
  
  //get table name and revision table name, getFieldTableName NOT WORK, so use getDedicatedDataTableName
  $table = $table_mapping->getDedicatedDataTableName($field_storage_definition);
  //try/catch
  $revision_table = NULL;
  if ($entity_type->isRevisionable() && $field_storage_definition->isRevisionable()) {
    if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
      $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage_definition);
    }
    elseif ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
      $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
    }
  }
  
  // Save changes to the installed field schema.
  if ($field_schema_data) {
    $_column = $table_mapping->getFieldColumnName($field_storage_definition, $property);
    //Update schema definition in database
    unset($field_schema_data[$table]['fields'][$_column]);
    if ($revision_table) {
      unset($field_schema_data[$revision_table]['fields'][$_column]);
    }
    $entity_storage_schema_sql->set($schema_key, $field_schema_data);
    //Try to drop field data
    \Drupal::database()->schema()->dropField($table, $_column);
  }
}
