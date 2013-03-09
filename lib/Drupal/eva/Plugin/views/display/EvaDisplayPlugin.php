<?php

/**
 * @file
 * Contains Drupal\eva\Plugin\views\display\EvaDisplayPlugin.
 */

namespace Drupal\eva\Plugin\views\display;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityManager;

/**
 * A plugin to handle defaults on a view.
 *
 * @ingroup views_display_plugins
 *
 * @Plugin(
 *   id = "eva",
 *   title = @Translation("EVA Field"),
 *   help = @Translation("View to be attached to any entity."),
 *   theme = "views_view",
 *   uses_hook_entity_view = TRUE
 * )
 */
class EvaDisplayPlugin extends DisplayPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['entity_type'] = array('default' => '');
    $options['bundles'] = array('default' => array());
    $options['argument_mode'] = array('default' => 'id');
    $options['default_argument'] = array('default' => '');
    $options['show_title'] = 0;
    $options['exposed_form_as_field'] = 0;

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['entity_view'] = array(
      'title' => t('Entity content settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    if ($entity_type = $this->getOption('entity_type')) {
      $entity_info = entity_get_info($entity_type);
      $type_name = $entity_info['label'];
      $bundle_names = array();
      $bundle_info = entity_get_bundles($entity_type);
      foreach ($this->getOption('bundles') as $bundle) {
        $bundle_names[] = $bundle_info[$bundle]['label'];
      }
    }

    $options['entity_type'] = array(
      'category' => 'entity_view',
      'title' => t('Entity type'),
      'value' => empty($type_name) ? t('None') : $type_name,
    );

    $options['bundles'] = array(
      'category' => 'entity_view',
      'title' => t('Bundles'),
      'value' => empty($bundle_names) ? t('All') : implode(', ', $bundle_names),
    );

    $argument_mode = $this->getOption('argument_mode');
    $options['arguments'] = array(
      'category' => 'entity_view',
      'title' => t('Arguments'),
      'value' => empty($argument_mode) ? t('None') : check_plain($argument_mode),
    );

    $options['show_title'] = array(
      'category' => 'entity_view',
      'title' => t('Show title'),
      'value' => $this->getOption('show_title') ? t('Yes') : t('No'),
    );

    $options['exposed_form_as_field'] = array(
      'category' => 'entity_view',
      'title' => t('Exposed Form as Field'),
      'value' => $this->getOption('exposed_form_as_field') ? t('Yes') : t('No'),
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_info = drupal_container()->get('plugin.manager.entity')->getDefinitions();
    $entity_type = $this->getOption('entity_type');

    switch ($form_state['section']) {
      case 'entity_type':
        foreach ($entity_info as $type => $info) {
          if (!empty($info['render_controller_class'])) {
            $entity_names[$type] = $info['label'];
          }
        }

        $form['#title'] .= t('Entity type');
        $form['entity_type'] = array(
          '#type' => 'radios',
          '#required' => TRUE,
          '#title' => t("Attach this display to the following entity type"),
          '#options' => $entity_names,
          '#default_value' => $this->getOption('entity_type'),
        );
        break;

      case 'bundles':
        foreach (entity_get_bundles($entity_type) as $bundle => $info) {
          $options[$bundle] = $info['label'];
        }
        $form['#title'] .= t('Bundles');
        $form['bundles'] = array(
          '#type' => 'checkboxes',
          '#title' => t("Attach this display to the following bundles.  If no bundles are selected, the display will be attached to all."),
          '#options' => $options,
          '#default_value' => $this->getOption('bundles'),
        );
        break;

      case 'arguments':
        $form['#title'] .= t('Arguments');
        $default = $this->getOption('argument_mode');
        $options = array(
          'none' => t("No special handling"),
          'id' => t("Use the ID of the entity the view is attached to"),
          'token' => t("Use tokens from the entity the view is attached to"),
        );

        $form['argument_mode'] = array(
          '#type' => 'radios',
          '#title' => t("How should this display populate the view's arguments?"),
          '#options' => $options,
          '#default_value' => $default,
        );

        $form['token'] = array(
          '#type' => 'fieldset',
          '#title' => t('Token replacement'),
          '#collapsible' => TRUE,
          '#states' => array(
            'visible' => array(
              ':input[name=argument_mode]' => array('value' => 'token'),
            ),
          ),
        );

        $form['token']['default_argument'] = array(
          '#title' => t('Arguments'),
          '#type' => 'textfield',
          '#default_value' => $this->getOption('default_argument'),
          '#description' => t('You may use token replacement to provide arguments based on the current entity. Separate arguments with "/".'),
        );
        break;

      case 'show_title':
        $form['#title'] .= t('Show title');
        $form['show_title'] = array(
          '#type' => 'checkbox',
          '#title' => t('Show the title of the view above the attached view.'),
          '#default_value' => $this->getOption('show_title'),
        );
        break;
      case 'exposed_form_as_field':
        $form['#title'] .= t('Exposed Form as Field');
        $form['exposed_form_as_field'] = array(
          '#type' => 'checkbox',
          '#title' => t('Split off Exposed Form as Separate Field'),
          '#default_value' => $this->getOption('exposed_form_as_field'),
          '#description' => t('Check this box to have a separate field for this view\'s exposed form on the "Manage Display" tab'),
        );
    }
  }

  public function validateOptionsForm(&$form, &$form_state) {
    $errors = parent::validateOptionsForm($form, $form_state);

    $entity_type = $this->getOption('entity_type');
    if (empty($entity_type)) {
      $errors[] = t('Display @display must have an entity type selected.', array('@display' => $this->display->display_title));
    }
    return $errors;
  }

  public function submitOptionsForm(&$form, &$form_state) {
    parent::submitOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'entity_type':
        $new_entity = $form_state['values']['entity_type'];
        $old_entity = $this->getOption('entity_type');

        $this->setOption('entity_type', $new_entity);
        if ($new_entity != $old_entity) {
          // Each entity has its own list of bundles and view modes. If there's
          // only one on the new type, we can select it automatically. Otherwise
          // we need to wipe the options and start over.
          $new_entity_info = drupal_container()->get('plugin.manager.entity')->getDefinition($new_entity);
          $new_bundles_keys = entity_get_bundles($new_entity);
          $new_bundles = array();
          if (count($new_bundle_keys) == 1) {
            $new_bundles[] = $new_bundle_keys[0];
          }
          $this->setOption('bundles', $new_bundles);
        }
        break;
      case 'bundles':
        $this->setOption('bundles', array_values(array_filter($form_state['values']['bundles'])));
        break;
      case 'arguments':
        $this->setOption('argument_mode', $form_state['values']['argument_mode']);
        if ($form_state['values']['argument_mode'] == 'token') {
          $this->setOption('default_argument', $form_state['values']['default_argument']);
        }
        else {
          $this->setOption('default_argument', NULL);
        }
        break;
      case 'show_title':
        $this->setOption('show_title', $form_state['values']['show_title']);
        break;
      case 'exposed_form_as_field':
        $this->setOption('exposed_form_as_field', $form_state['values']['exposed_form_as_field']);
        break;
    }
  }

  public function preExecute() {
    parent::preExecute();
    
    if (isset($this->view->current_entity)) {
      $entity = $this->view->current_entity;
      $entity_type = $this->view->display_handler->getOption('entity_type');
      $entity_info = drupal_container()->get('plugin.manager.entity')->getDefinition($entity_type);
  
      $arg_mode = $this->view->display_handler->getOption('argument_mode');
      if ($arg_mode == 'token') {
        if ($token_string = $this->view->display_handler->getOption('default_argument')) {
          // Now do the token replacement.
          $token_values = eva_get_arguments_from_token_string($token_string, $entity_type, $entity);
          $new_args = array();
          // We have to be careful to only replace arguments that have tokens.
          foreach ($token_values as $key => $value) {
            $new_args[$key] = $value;
          }
  
          $this->view->args = $new_args;
        }
      }
      elseif ($arg_mode == 'id') {
        $this->view->args = array($entity->{$entity_info['entity keys']['id']});
      }
    }
  }
  
  public function getPath() {
    if (isset($this->view->current_entity)) { 
     $uri = $this->view->current_entity->uri();
      if ($uri) {
        $uri['options']['absolute'] = TRUE;
        return url($uri['path'], $uri['options']);
      }
    }
    return parent::get_path();
  }
 
  function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    if (!isset($this->view->override_path)) {
      $this->view->override_path = $_GET['q'];
    }

    $data = $this->view->render();
    if (!empty($this->view->result) || $this->getOption('empty') || !empty($this->view->style_plugin->definition['even empty'])) {
      return $data;
    }
  }
}


