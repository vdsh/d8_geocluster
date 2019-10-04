<?php

/**
 * @file
 * Contains \Drupal\geocluster\Plugin\views\display_extender\GeoclusterViewsDisplayExtender.
 */

namespace Drupal\geocluster\Plugin\views\display_extender;

use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geocluster\GeoclusterConfigBackendInterface;


define('GEOCLUSTER_VIEWS_SECTION', 'style_options');

/**
 * Display extender class that integrates geocluster config with views.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "geocluster_views_display_extender",
 *   title = @Translation("Cluster results"),
 *   help = @Translation("Cluster results on aggregation."),
 *   no_ui = FALSE,
 * )
 */
class GeoclusterViewsDisplayExtender extends DisplayExtenderPluginBase implements GeoclusterConfigBackendInterface {
  
  /**
   * @var GeoclusterConfig
   */
  var $config;

  function init($view, $display, &$options = NULL) {
    parent::init($view, $display, $options);
    $this->config = geocluster_init_config($this);
  }
  
  /**
  * {@inheritdoc}
  */
  public function defineOptions() {
    return [
      'geocluster_enabled' => ['default' => FALSE],
      'geocluster_options' => ['default' => array()],
    ] + parent::defineOptions();
    //return parent::defineOptions();
  }
  
   /**
   * Provide the key options for this plugin.
   */
  public function defineOptionsAlter(&$options) {
    // options_definition() doesn't work for display_extender plugins.
    // see http://drupal.org/node/681468#comment-4384814
    // and http://drupal.org/node/1616540
    //var_dump($this->config->options_definition());exit();
   // var_dump( $this->config->options_definition());
   // var_dump( $options);
   // exit('ici');
   // done in defineOptions
   // $options = array_merge($options, $this->config->options_definition());
  }
  /**
   * Provide the default summary for options and category in the views UI.
   */
  public function optionsSummary(&$categories, &$options) {
  }
  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if ($storage['section'] == GEOCLUSTER_VIEWS_SECTION) {
      $this->config->options_form($form, $form_state);
    }
  }
  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $this->config->options_validate($form, $form_state);
  }
  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $this->config->options_submit($form, $form_state);
  }
  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {
    if ($this->get_option('geocluster_enabled')) {
      if ($algorithm = geocluster_init_algorithm($this->config)) {
        $algorithm->before_pre_execute();
      }
    }
  }
  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() {
    if ($this->get_option('geocluster_enabled')) {
      if ($algorithm = geocluster_init_algorithm($this->config)) {
        $algorithm->pre_execute();
      }
    }
  }
  /**
   * Static member function to list which sections are defaultable
   * and what items each section contains.
   */
  public function defaultableSections(&$sections, $section = NULL) { }
  /**
   * Identify whether or not the current display has custom metadata defined.
   */
   
  /**
   * Returns a display option value.
   */
  public function get_option($option) {
    return $this->options[$option];
  }

  /**
   * Sets an option value.
   */
  public function set_option($option, $value) {
    $this->options[$option] = $value;
    
  }

  /**
   * Get the appropriate option display handler (default or overridden).
   *
   * @return views_display
   */
   /*
  protected function &get_option_handler() {
    if ($this->get_display()->is_defaulted(GEOCLUSTER_VIEWS_SECTION) && isset($this->view->display['default'])) {
      return $this->view->display['default']->handler;
    }
    // Else.
    return $this->display;
  }
  */

  /**
   * Returns the view that the configuration is attached to.
   * @return View
   */
  public function get_view() {
    return $this->view;
  }
  
  /**
   * Returns the display of the configuration.
   * @return views_plugin_display
   */
  public function get_display() {
    return $this->view->display_handler;
  }
}


