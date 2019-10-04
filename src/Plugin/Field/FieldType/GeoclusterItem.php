<?php

namespace Drupal\geocluster\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;



/**
 * Plugin implementation of the 'geocluster' field type.
 *
 * @FieldType(
 *   id = "geocluster",
 *   label = @Translation("geocluster"),
 *   description = @Translation("This field stores geospatial information."),
 *   default_widget = "geofield_latlon",
 *   default_formatter = "geofield_default"
 * )
 */
class GeoclusterItem extends \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    $field_schema = parent::schema($field);
    
    // Adds separate columns for the geohash indices, from length 1 to max.
    for ($i = 1; $i <= GEOCLUSTER_GEOHASH_LENGTH; $i++) {
      $name = 'geocluster_index_' . $i;
      $field_schema['columns'][$name] = [
        'description' => 'Geocluster index level ' . $i,
        'type' => 'varchar',
        'length' => GEOCLUSTER_GEOHASH_LENGTH,
        'not null' => FALSE,
      ];
      
       $field_schema['indexes'][$name] = [$name];
    }
    
    return $field_schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    
     // Adds propety definitions for the geohash indexes, from length 1 to max.
    for ($i = 1; $i <= GEOCLUSTER_GEOHASH_LENGTH; $i++) {
      $name = 'geocluster_index_' . $i;
      $properties[$name] = DataDefinition::create('string')
      ->setLabel(t('Geocluster index lvl '.$i));
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return parent::fieldSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
    
    for ($i = GEOCLUSTER_GEOHASH_LENGTH; $i > 0; $i--) {
      $name = 'geocluster_index_' . $i;
      $this->$name = $this->_geocluster_get_geohash_prefix($this->geohash, $i);
    }
  }
  
  /**
   * Get a geohash prefix of a specified, maximum length.
   *
   * @param $geohash
   * @param $length
   * @return string
   */
  private function _geocluster_get_geohash_prefix($geohash, $length) {
    return substr($geohash, 0, min($length, strlen($geohash)));
  }



  /**
   * {@inheritdoc}
   */
  public function prepareCache() {
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return parent::generateSampleValue($field_definition);
  }

}
