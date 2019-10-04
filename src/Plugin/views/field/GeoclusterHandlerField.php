<?php
 
/**
 * @file
 * Definition of Drupal\geocluster\Plugin\views\field\GeoclusterHandlerField
 */
 
namespace Drupal\geocluster\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Default Field handler 
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("geocluster_handler_field")
 */
class GeoclusterHandlerField extends FieldPluginBase {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
 
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }
  
  /**
  * Provide the options form.
  */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    /*
    $cluster_field_options = $this->_get_cluster_field_options();
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
    $form = $more_form + $form;*/
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $field_name = $this->field;
    if (isset($values->$field_name)) {
      return $values->$field_name;
    }
    return "";
  }
  /*
  private function _get_cluster_field_options() {
    // Inspired by geofield.
    $handlers = $this->get_display()->get_handlers('field');
    $cluster_field_options = array(
      '' => '<none>',
    );
    foreach ($handlers as $handler) {
      $field_info = NULL;
      if (!empty($handler->field_info)) {
        $field_info = $handler->field_info;
      }
      elseif ($this->is_entity_views_handler($handler)) { //D8 Upgrade: in which case would that happen?
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
    }
    return $cluster_field_options;
  }*/
  
}