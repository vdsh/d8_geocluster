<?php

namespace Drupal\geocluster\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for GeoclusterAlgorithm plugins.
 */
abstract class GeoclusterAlgorithmBase extends PluginBase implements GeoclusterAlgorithmInterface{

  /**
   * @var GeoclusterConfigBackendInterface
   */
  var $config;

  /**
   * Reference to Geofield field handler on which to perform clustering.
   *
   * @var views_handler_field
   */
  var $field_handler;

  /**
   * Minimum cluster distance, as defined by GeoclusterConfigViewsDisplayExtender.
   * @todo: document unit.
   *
   * @var float
   */
  var $cluster_distance;

  /**
   * Current zoom level for clustering.
   *
   * @var int
   */
  var $zoom_level;

  /**
   * Resolution in meters / pixel based on zoom_level.
   * @see GeoclusterHelper::resultions().
   *
   * @var float
   */
  var $resolution;

  /**
   * Geohash length for clustering by a specified distance in pixels.
   *
   * @var int
   */
  var $geohash_length;

  /**
   * Items being clustered.
   */
  var $values;

  /**
   * Total number of items within clusters.
   */
  protected $total_items;

  /**
   * Flag to skip whole clustering process.
   */
  protected $skipClustering;

  /**
   * Constructor.
   *
   * @param $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // $config, $cluster_distance, $zoom, $field_handler in D7, now included in $configuration
    // Check if cluster distance is negative, if so disable clustering.
    $this->skipClustering($configuration['cluster_distance'] < 0);
    $this->config = $configuration['config'];
    $this->field_handler = $configuration['field_handler'];
    $this->cluster_distance = $configuration['cluster_distance'];
    $this->zoom_level = $configuration['zoom'];
    $resolutions = \Drupal\geocluster\Utility\GeoclusterHelper::resolutions();
    $this->resolution = $resolutions[$configuration['zoom']];
    $this->geohash_length = \Drupal\geocluster\Utility\GeohashHelper::lengthFromDistance($this->cluster_distance, $this->resolution);
    $this->after_construct();
  }

  /**
   * Perform any clustering tasks before the views query will be executed.
   */
  abstract function pre_execute();

  /**
   * Perform any clustering tasks after the views query has been executed.
   */
  abstract function post_execute();

  /**
   * Allows to skip the clustering process.
   *
   * @param null|bool $skip
   *   If the parameter is give it set's the state of the skipping flag.
   *
   * @return bool
   *   TRUE if the clustering is disabled, FALSE otherwise.
   */
  public function skipClustering($skip = NULL) {
    if (isset($skip)) {
      $this->skipClustering = (bool) $skip;
    }
    return $this->skipClustering;
  }

  /*** DEBUGGING-RELATED WRAPPER FUNCTIONS ***/

  function after_construct() {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('devel')){
      timer_start("geocluster");
      $debug =
        "zoom: " . $this->zoom_level .
          " , resolution: " . $this->resolution .
          " , distance: " . $this->cluster_distance .
          " , geohash_length: " . $this->geohash_length;
      dd("");
      dd(get_class($this));
      dd(timer_read("geocluster") . "ms: setup: " . $debug);
    }
  }

  function before_pre_execute() {
  }

  function before_post_execute() {
    $view = $this->config->get_view();
    $this->values = &$view->result;

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('devel')){
      $query =  Database::getConnection()->prefixTables(vsprintf($view->build_info['query'], $view->build_info['query_args']));
      $replacements = module_invoke_all('views_query_substitutions', $view);
      $query = str_replace(array_keys($replacements), $replacements, $query);
      // dd($query);
      dd(timer_read("geocluster") . "ms: started clustering");
    }
  }

  function after_post_execute() {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('devel')){
      dd(timer_read("geocluster") . "ms: merged & finalized clusters: " . count($this->values));
      dd(timer_read("geocluster") . "ms: total items within clusters: " . $this->total_items);
      timer_stop("geocluster");
    }
  }

  /*** GETTERS & SETTERS ***/

  /**
   * @return float
   */
  public function getClusterDistance() {
    return $this->cluster_distance;
  }

  /**
   * @return \views_handler_field
   */
  public function getFieldHandler() {
    return $this->field_handler;
  }

  /**
   * @return float
   */
  public function getResolution() {
    return $this->resolution;
  }

  /**
   * @return int
   */
  public function getZoomLevel() {
    return $this->zoom_level;
  }

  /**
   * @return int
   */
  public function getGeohashLength() {
    return $this->geohash_length;
  }

  public function setValues(&$values) {
    $this->values = &$values;
  }

  public function getValues() {
    return $this->values;
  }

}
