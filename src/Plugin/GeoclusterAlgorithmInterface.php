<?php

namespace Drupal\geocluster\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\HandlerBase;

/**
 * Defines an interface for Geocluster Algorithm plugins.
 */
interface GeoclusterAlgorithmInterface extends PluginInspectionInterface {

  /**
   * Constructor.
   *
   * @param $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition);
  
  /**
   * Perform any clustering tasks before the views query will be executed.
   */
  public function pre_execute();

  /**
   * Perform any clustering tasks after the views query has been executed.
   */
  public function post_execute();

  /**
   * Allows to skip the clustering process.
   *
   * @param null|bool $skip
   *   If the parameter is give it set's the state of the skipping flag.
   *
   * @return bool
   *   TRUE if the clustering is disabled, FALSE otherwise.
   */
  public function skipClustering($skip);

  /*** DEBUGGING-RELATED WRAPPER FUNCTIONS ***/

  function after_construct();

  function before_pre_execute();

  function before_post_execute();

  function after_post_execute();

  /*** GETTERS & SETTERS ***/

  /**
   * @return float
   */
  public function getClusterDistance();

  /**
   * @return \views_handler_field
   */
  public function getFieldHandler();

  /**
   * @return float
   */
  public function getResolution();

  /**
   * @return int
   */
  public function getZoomLevel();

  /**
   * @return int
   */
  public function getGeohashLength();

  public function setValues(&$values);

  public function getValues();

}
