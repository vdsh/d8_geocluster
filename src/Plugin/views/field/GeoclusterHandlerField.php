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
}