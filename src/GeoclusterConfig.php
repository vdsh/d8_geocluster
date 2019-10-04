<?php


namespace Drupal\geocluster;

use Drupal\geocluster\GeoclusterConfigBackendInterface;

define('GEOCLUSTER_DEFAULT_DISTANCE', 65);
define('GEOCLUSTER_DEFAULT_ALGORITHM', 'mysql_algorithm');


/**
 * Encapsulates the geocluster config.
 */
class GeoclusterConfig implements GeoclusterConfigBackendInterface {

  /**
   * @var GeoclusterConfigBackendInterface
   */
  var $config_backend;

  function __construct($config_backend) {
    $this->config_backend = $config_backend;
  }

  function options_definition() {
    $options = array();
    $options['geocluster_enabled']['default'] = FALSE;
    $options['geocluster_options'] = array();
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $cluster_field_options = $this->get_cluster_field_options();
    if (count($cluster_field_options) == 1) {
      $more_form['error'] = array(
        '#markup' => 'Please add at least 1 geofield to the view',
      );
    }
    else {
      // Add a checkbox to enable clustering.
      $more_form['geocluster_enabled'] = array(
        '#type' => 'checkbox',
        '#title' => 'Enable geocluster for this search.',
        '#default_value' => $this->get_option('geocluster_enabled'),
        // '#description' => t("@todo: description"),
      );

      // An additional fieldset provides additional options.
      $geocluster_options = $this->get_option('geocluster_options');
      $more_form['geocluster_options'] = array(
        '#type' => 'fieldset',
        '#title' => 'Geocluster options',
        '#tree' => TRUE,
        '#states' => array(
          'visible' => array(
            ':input[name="geocluster_enabled"]' => array('checked' => TRUE),
          ),
        ),
      );
      $algorithm_options = $this->get_algorithm_options();
      $more_form['geocluster_options']['algorithm'] = array(
        '#type' => 'select',
        '#title' => t('Clustering algorithm'),
        '#description' => t('Select a geocluster algorithm to be used.'),
        '#options' => $algorithm_options,
        '#default_value' => $geocluster_options['algorithm'] ? $geocluster_options['algorithm'] : GEOCLUSTER_DEFAULT_ALGORITHM,
      );
      $more_form['geocluster_options']['cluster_field'] = array(
        '#type' => 'select',
        '#title' => t('Cluster field'),
        '#description' => t('Select the geofield to be used for clustering.?'),
        '#options' => $cluster_field_options,
        '#default_value' => $geocluster_options['cluster_field'] ? $geocluster_options['cluster_field'] : '',
      );
      $more_form['geocluster_options']['cluster_distance'] = array(
        '#type' => 'textfield',
        '#title' => t('Cluster distance'),
        '#default_value' => $geocluster_options['cluster_distance'] ? $geocluster_options['cluster_distance'] : GEOCLUSTER_DEFAULT_DISTANCE,
        '#description' => t('Specify the cluster distance.'),
      );
      $more_form['geocluster_options']['enable_bbox_support'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable bbox support'),
        '#default_value' => !empty($geocluster_options['enable_bbox_support']),
        '#description' => t('If enabled available Views GeoJSON bbox support will be enhanced.'),
      );

      $more_form['geocluster_options']['advanced'] = array(
        '#type' => 'fieldset',
        '#title' => t('Advanced'),
        '#collapsed' => TRUE,
        '#collapsible' => TRUE,
      );
      $more_form['geocluster_options']['advanced']['accept_parameter']['cluster_distance'] = array(
        '#type' => 'checkbox',
        '#title' => t('Accept URL parameter to set cluster distance'),
        '#default_value' => !isset($geocluster_options['advanced']['accept_parameter']['cluster_distance']) || !empty($geocluster_options['advanced']['accept_parameter']['cluster_distance']),
        '#description' => t('If enabled the GET parameter "cluster_distance" will be used to set the cluster distance.'),
      );
      $more_form['geocluster_options']['advanced']['accept_parameter']['zoom'] = array(
        '#type' => 'checkbox',
        '#title' => t('Accept URL parameter to set zoom level'),
        '#default_value' => !isset($geocluster_options['advanced']['accept_parameter']['zoom']) || !empty($geocluster_options['advanced']['accept_parameter']['zoom']),
        '#description' => t('If enabled the GET parameter "zoom" will be used to set the current zoom level.'),
      );

      $cluster_distance_per_zoom_level = '';
      if (!empty($geocluster_options['advanced']['cluster_distance_per_zoom_level']) && is_array($geocluster_options['advanced']['cluster_distance_per_zoom_level'])) {
        $cluster_distance_per_zoom_level = $geocluster_options['advanced']['cluster_distance_per_zoom_level'];
        array_walk($cluster_distance_per_zoom_level, function (&$val, $key) {
            $val = $key . '|' . $val;
        });
        $cluster_distance_per_zoom_level = implode("\n", $cluster_distance_per_zoom_level);
      }
      $more_form['geocluster_options']['advanced']['cluster_distance_per_zoom_level'] = array(
        '#type' => 'textarea',
        '#title' => t('Accept URL parameter to set zoom level'),
        '#default_value' => $cluster_distance_per_zoom_level,
        '#description' => t('Define a zoom level and a cluster distance per line. Format: zoomLevel|Distance e.g. 12|65. Fallback is the default cluster distance. Set distance to -1 to disable clustering.'),
      );
    }
    $form = $more_form + $form;
  }

  function options_validate(&$form, &$form_state) {
  }

  function options_submit(&$form, &$form_state) {
    $geocluster_options = $form_state->getValue('geocluster_optionaas');
    
    $this->set_option('geocluster_enabled', !empty($form_state->getValue('geocluster_enabled')));
    
    $geocluster_options = $form_state->getValue('geocluster_options');
    if ($geocluster_options) {
      // Handle the cluster_distance_per_zoom_level option. Split it into an
      // array, with the zoom level as key and the distance as value.
      $cluster_distance_per_zoom_level_keys = preg_replace('/\|.+$/im', '', $geocluster_options['advanced']['cluster_distance_per_zoom_level']);
      $cluster_distance_per_zoom_level_distances = preg_replace('/^.+?\|/mi', '', $geocluster_options['advanced']['cluster_distance_per_zoom_level']);
      $cluster_distance_per_zoom_level_keys = array_map('trim', explode("\n", $cluster_distance_per_zoom_level_keys));
      $cluster_distance_per_zoom_level_distances = array_map('trim', explode("\n", $cluster_distance_per_zoom_level_distances));
      $geocluster_options['advanced']['cluster_distance_per_zoom_level'] = array_combine($cluster_distance_per_zoom_level_keys, $cluster_distance_per_zoom_level_distances);

      $this->set_option('geocluster_options', $geocluster_options);

      // If geocluster is enabled make sure the aggregation settings are set
      // properly.
      
      if ($this->get_option('geocluster_enabled')) {
        if ($geocluster_options['algorithm'] == 'mysql_algorithm') {
          if (!$this->get_option('group_by')) {
            $this->set_option('group_by', TRUE);
            drupal_set_message(t('The <strong>use aggregation</strong> setting has been <em>enabled</em> as a requirement by the MySQL-based geocluster algorithm.'));
          }
        }
        elseif ($geocluster_options['algorithm'] == 'php_algorithm') {
          if ($this->get_option('group_by')) {
            $this->set_option('group_by', FALSE);
            drupal_set_message(t('The <strong>use aggregation</strong> setting has been <em>disabled</em> as a requirement by the PHP-based geocluster algorithm.'));
          }
        }
      }
      else {
        // Ensure aggregation is disabled if it's not supported by this query
        // handler. It can happen that a display doesn't return a query object.
        $query = $this->get_display()->query();
        if (!empty($query) && $query->get_aggregation_info() === NULL &&  $this->get_option('group_by')) {
          $this->set_option('group_by', FALSE);
        }
      }
    }
  }

  function get_cluster_field_options() {
    // find all fields of type 'geofield' associated to that display
    $handlers = $this->get_display()->handlers['field'];
    $cluster_field_options = array(
      '' => '<none>',
    );
    foreach ($handlers as $handler) {
      /* TODO: do better than this, but the field definition is protected */
      if ($handler->options['type'] == 'geofield_default')
      {
        
        $cluster_field_options[$handler->options['id']] = (!empty($handler->options['label'])) ? $handler->options['label'] : $handler->options['id'];
      }
     // var_dump($handler->options['type']);exit();
      /*
      $field_info = NULL;
      if (!empty($handler->field_info)) {
        $field_info = $handler->field_info;
      }
      elseif ($this->is_entity_views_handler($handler)) {
        // Strip the basic field name from the entity views handler field and
        // fetch the field info for it.
        $property = EntityFieldHandlerHelper::get_selector_field_name($handler->real_field);
        if ($field_name = EntityFieldHandlerHelper::get_selector_field_name(substr($handler->real_field, 0, strpos($handler->real_field, ':' . $property)), ':')) {
          $field_info = field_info_field($field_name);
        }
      }
      if (!empty($field_info['type']) && $field_info['type'] == 'geofield') {
        $cluster_field_options[$handler->options['id']] = (!empty($handler->options['label'])) ? $handler->options['label'] : $handler->options['id'];
      }
      */
    }
   // var_dump($cluster_field_options);exit();
    return $cluster_field_options;
  }

  /**
   * Provide a list of available geocluster algorithm options.
   * @return array
   */
  protected function get_algorithm_options() {
    $options = array();
    
    $type = \Drupal::service('plugin.manager.geocluster_algorithm');
    $algorithms = $type->getDefinitions();
    
    foreach ($algorithms as $id => $algorithm) {
      $options[$id] = $algorithm['admin_label']->__toString();
    }
    return $options;
  }

  /**
   * Returns a configuration option value.
   */
  public function get_option($option) {
    return $this->config_backend->get_option($option);
  }

  /**
   * Sets an option value.
   */
  public function set_option($option, $value) {
    $this->config_backend->set_option($option, $value);
  }

  /**
   * Returns the view that the configuration is attached to.
   * @return View
   */
  public function get_view() {
    return $this->config_backend->get_view();
  }

  /**
   * Returns the display of the configuration.
   * @return views_plugin_display
  */
  public function get_display() {
    // return $this->config_backend->view->display_handler;
    return $this->config_backend->get_display();
  }

  /**
   * Checks if a field handler class is handled by the entity module.
   *
   * @param object $field_handler_instance
   *   The field handler instance to check.
   *
   * @return bool
   *   TRUE if the field is handled by the entity module views integration.
   */
   /*
  public function is_entity_views_handler($field_handler_instance) {
    $static_cache = &drupal_static(__METHOD__, array());
    $handler_class = get_class($field_handler_instance);

    if (!isset($static_cache[$handler_class])) {
      $static_cache[$handler_class] = FALSE;
      foreach (entity_views_get_field_handlers() as $field_handler) {
        if ($field_handler_instance instanceof $field_handler) {
          return $static_cache[$handler_class] = TRUE;
        }
      }
    }
    return $static_cache[$handler_class];
  }
  */
}