<?php

namespace Drupal\geocluster\Plugin\GeoclusterAlgorithm;

use Drupal\Component\Annotation\Plugin;
use Drupal\geocluster\Plugin\GeoclusterAlgorithmBase;
use Drupal\geocluster\Utility\GeohashHelper;
use Drupal\geocluster\Utility\GeoclusterHelper;

/**
 * Abstract definition of a Geohash algorithm.
 *
 */
abstract class GeohashGeoclusterAlgorithm extends GeoclusterAlgorithmBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }
  
  function pre_execute(){
  }

  /**
   * Perform clustering on the aggregated views result set.
   */
  function post_execute() {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('devel')){
      // dd(\Drupal\Component\Utility\Timer::read("geocluster") . "ms: items from database: " . count($this->values));
    }
    geophp_load();
    $results_by_geohash = $this->preClusterByGeohash();
    if ($moduleHandler->moduleExists('devel')){
      // dd(\Drupal\Component\Utility\Timer::read("geocluster") . "ms: pre-clustered by geohash: " . count($this->values));
    }

    $this->clusterByNeighborCheck($results_by_geohash);

    $this->finalizeClusters();
  }

  /**
   * Create initial clustering from geohash grid.
   *
   * No default implementation, to be implemented by algorithm, if needed.
   */
  protected function preClusterByGeohash() {
  }

  static function sortResultsByCount($a, $b) {
    $item = current($a);
    $item2 = current($b);
    return $item->geocluster_count < $item2->geocluster_count;
  }

  /**
   * Create final clusters by checking for overlapping neighbors.
   *
   * @param $results_by_geohash
   */
  protected function clusterByNeighborCheck(&$results_by_geohash) {
    ksort($results_by_geohash);
    foreach ($results_by_geohash as $current_hash => &$results) {
      if (empty($current_hash)) {
        continue;
      }
      $item_key = current(array_keys($results));
      $item = $results[$item_key];
      // Check top right neighbor hashes for overlapping points.
      // Top-right is enough because by the way geohash is structured,
      // future geohashes are always top, topright or right
      $hash_stack = GeohashHelper::getTopRightNeighbors($current_hash);
      foreach ($hash_stack as $hash) {
        if (isset($results_by_geohash[$hash])) {
          $other_item_key = current(array_keys($results_by_geohash[$hash]));
          $geometry = $this->getGeometry($this->values[$item_key]);
          $other_geometry = $this->getGeometry($this->values[$other_item_key]);
          if ($this->shouldCluster($geometry, $other_geometry)) {
            $this->addCluster($item_key, $other_item_key, $current_hash, $hash, $results_by_geohash);
            if (!isset($results_by_geohash[$current_hash])) {
              continue 2;
            }
          }
        }
      }
    }
  }

  /**
   * Finalize clusters.
   */
  protected function finalizeClusters() {
    foreach ($this->values as &$value) {
      if ($value->geocluster_count > 1) {
        $value->clustered = TRUE;
      }
    }
  }

  /*** ALGORITHM HELPERS ***/

  protected function initCluster(&$value) {
    $point = \Drupal::service('geofield.wkt_generator')->wktBuildPoint(array($value->geocluster_lon, $value->geocluster_lat));
    $value->geocluster_geometry = \Drupal::service('geofield.geophp')->load($point);
    $value->clustered = TRUE;
    return $value->geocluster_count;
  }

  /**
   * Determine if two geofields should be clustered as of their distance.
   */
  protected function shouldCluster($geometry, $otherGeometry, $size = null) {
    // Calculate distance.
    $distance = GeoclusterHelper::distance_pixels($geometry, $otherGeometry, $this->resolution);
    return $distance <= $this->cluster_distance;
  }

  /**
   * Cluster two given rows.
   *
   * @param $row_id the first row to be clustered
   * @param $row_id2 the second row to be clustered
   */
  protected function addCluster($row_id, $row_id2, $hash, $hash2, &$entities_by_geohash) {
    $result1 = &$this->values[$row_id]; $result2 = &$this->values[$row_id2];

    // Calculate new center from all points.
    $center = GeoclusterHelper::getCenter(array($result1->geocluster_geometry, $result2->geocluster_geometry), array($result1->geocluster_count, $result2->geocluster_count));
    $result1->geocluster_geometry = $center;
    $result1->geocluster_lat = $center->getY();
    $result1->geocluster_lon = $center->getX();
	
	// Merge cluster data.
    $result1->geocluster_ids .= ',' . $result2->geocluster_ids;
    $result1->geocluster_count += $result2->geocluster_count;

    // Remove other result data that has been merged into the cluster.
    unset($this->values[$row_id2]);
    unset($entities_by_geohash[$hash2][$row_id2]);
    if (count($entities_by_geohash[$hash2]) == 0) {
      unset($entities_by_geohash[$hash2]);
    }
  }

  protected function getGeometry($result) {
    return $result->geocluster_geometry;
  }

}
