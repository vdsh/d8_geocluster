<?php

namespace Drupal\geocluster\Plugin\GeoclusterAlgorithm;

use Drupal\Component\Annotation\Plugin;


/**
 * Definition of the MySQLGeohashGeocluster algorithm.
 *
 *
 * @package Drupal\geocluster\Plugin
 *
  * @GeoclusterAlgorithm(
 *   id = "mysql_algorithm",
 *   admin_label = @Translation("MySQL Geohash geocluster algorithm")
 * )
 */
class MySQLGeohashGeoclusterAlgorithm extends GeohashGeoclusterAlgorithm {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }
  
  /**
   * Add the required geohash index to the query.
   *
   * This must be done before the view is built.
   */
  function before_pre_execute() {
    if($this->config->get_option('group_by')) {
      $view = $this->config->get_view();
      foreach ($view->field as $field_key => $field) {       
        if (isset($field->options) && isset($field->options['type']) && $field->options['type'] == 'geofield_default') { 
          // Set the group column to the appropriate geocluster index level.
          $group_column = 'geocluster_index_' . $this->getGeohashLength();
          $field->options['group_column'] = $group_column;
        }
      }
    }
  }

  /**
   * Add geocluster-based aggregation settings before executing the view.
   */
  function pre_execute() {
    if($this->config->get_option('group_by')) {
      $view = $this->config->get_view();
      foreach ($view->field as $field_key => $field) {
        if (isset($field->options) && isset($field->options['type']) && $field->options['type'] == 'geofield_default') { 
          $this->add_geocluster_group_by_settings($field);
        }
      }
    };
  }

  /**
   * Transform geohash-based, aggregated result into initial clustering.
   */
  protected function preClusterByGeohash() {
    $total_count = 0;
    $results_by_geohash = array();
    $group_field = $this->get_cluster_field_alias();
    foreach ($this->values as $row => &$value) {
      if (!isset($value->$group_field)) {
        continue;
      }
      $hash_prefix = $value->$group_field;
      $total_count += $this->initCluster($value);
      $results_by_geohash[$hash_prefix] = array(
        $row => $value,
      );
    }

    $this->total_items = $total_count;
    return $results_by_geohash;
  }

  /**
   * Use default neighbor check.
   */
  protected function clusterByNeighborCheck(&$results_by_geohash) {
    parent::clusterByNeighborCheck($results_by_geohash);
  }

  /**
   * Use default finalization.
   */
  protected function finalizeClusters() {
    parent::finalizeClusters();
  }

  /** HELPERS */

  /**
   * Adds geocluster-specific group_by settings to the query.
   *
   * @param $field views_handler_field
   */
  protected function add_geocluster_group_by_settings(&$field) {
    // Add additional fields.
    // Add concatenated list of grouped entity ids.
    $this->add_fields(array('geocluster_ids' => 'entity_id'), 'group_concat', $field);

    // Add count(entity_id).
    $this->add_fields(array('geocluster_count' => 'entity_id'), 'count', $field);

    // Add center point: avg(lat), avg(lng).
    $avg_fields = array(
      'geocluster_lat' => $field->field . '_' . 'lat',
      'geocluster_lon' => $field->field . '_' . 'lon',
    );
    $this->add_fields($avg_fields, 'avg', $field);

    // Add select fields required for field formatter.
    // @todo: Remove? We don't support clustered data to be used with field formatters at the moment.
    // $this->add_fields(array($field->field . '_' . 'geom'), 'min', $field);
  }

  protected function add_fields($additional_fields, $function = NULL, &$field) {
    foreach ($additional_fields as $field_key => $additional_field) {
      $params = array();
      if (!empty($function)) {
        $params['function'] = $function;
      }
      $view = $this->config->get_view();
      $view->query->addField($field->table_alias, $additional_field, $field_key, $params);
    }
  }

  protected function get_cluster_field_alias() {
    $name = $this->get_cluster_field_name();
    return $this->field_handler->aliases[$name];
  }

  protected function get_cluster_field_name() {
    return current($this->field_handler->group_fields);
  }
}


