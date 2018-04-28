<?php

namespace Drupal\eva;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class ViewDisplays {

  /**
   * The default cache bin.
   *
   * @var CacheBackendInterface
   */
  protected $default_cache;

  /**
   * The render cache bin.
   *
   * @var CacheBackendInterface
   */
  protected $render_cache;

  protected $config_factory;

  /**
   * The name of the cache key for the list of EVAs.
   * 
   * @var string
   */
  private $cache_id = 'eva.views_list';

  
  /**
   * Create a ViewDisplays helper class.
   *
   * @param CacheBackendInterface $default_cache
   * @param CacheBackendInterface $render_cache
   * @param ConfigFactoryInterface $config_factory
   */
  public function __construct(CacheBackendInterface $default_cache, CacheBackendInterface $render_cache, ConfigFactoryInterface $config_factory) {
    $this->default_cache = $default_cache;
    $this->render_cache = $render_cache;
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'),
      $container->get('cache.render'),
      $container->get('config.factory')
    );
  }

  /**
   * Get a list of views and displays attached to specific entities.
   *
   * This function will cache its results into the views cache, so it gets
   * cleared by Views appropriately.
   *
   * @param string|null $type
   *   The entity type we want to retrieve views for. If NULL is
   *   specified, views for all entity types will be returned.
   * @param bool $reset
   *   Force a rebuild of the data, defaults to false.
   *
   * @return array
   *   An array of view name/display name values, or an empty array().
   */
  public function get($type = NULL, $reset = FALSE) {
    // collect a list of EVAs and cache it
    $cache = $this->default_cache->get($this->cache_id);

    $used_views = [];
    if ($cache) {
      $used_views = $cache->data;
    }

    if (!$used_views) {
      $views = Views::getApplicableViews('uses_hook_entity_view');
    
      foreach ($views as $data) {
        list($view_name, $display_id) = $data;
        $view = Views::getView($view_name);
      
        // Initialize handlers, to determine if the view uses exposed filters.
        $view->setDisplay($display_id);
        $view->initHandlers();
        $display = $view->display_handler;
      
        $view_entity = $display->getOption('entity_type');
        $used_views[$view_entity][] = [
          'name' => $view_name,
          'id' => $view->storage->get('id'),
          'title' => 'EVA: ' . $view->storage->get('label') . ' - ' . $view->storage->getDisplay($display_id)['display_title'],
          'display' => $display_id,
          'bundles' => $display->getOption('bundles'),
          'uses exposed' => $display->usesExposed(),
        ];
        $view->destroy();
      }

      $this->default_cache->set($this->cache_id, $used_views, CacheBackendInterface::CACHE_PERMANENT);
    }
  
    if (!is_null($type)) {
      return isset($used_views[$type]) ? $used_views[$type] : [];
    }
    return $used_views;
  }

  /**
   * Cache clearing helper function.
   *
   * Reset the static cache in case any of the disabled modules implemented an
   * eva view.
  */
  public function reset() {
    $this->clear_detached(NULL, TRUE);
    $this->default_cache->invalidate($this->cache_id);
    $this->render_cache->deleteAll();
  }

  /**
   * Remove a removed extra field to entity displays.
   *
   * Run through all entity displays and clear out views that shouldn't be there.
   * This should be called at Views save and module install/remove
   *   $remove_one: force removal of a particular 'viewname_displayid' EVA
   *   $remove_all: remove all Evas.
   */
  public function clear_detached($remove_one = NULL, $remove_all = FALSE) {
    $views = $this->get();
  
    foreach ($views as $entity => $eva_info) {
      $config_names = $this->config_factory->listAll('core.entity_view_display.' . $entity);
      foreach ($config_names as $id) {
        $config = $this->config_factory->getEditable($id);
        $config_data = $config->get();
        foreach ($eva_info as $eva) {
          $eva_field_name = $eva['name'] . '_' . $eva['display'];
          // Eva should be considered for removal if one of these is true:
          // - all evas should be removed (i.e., when module is uninstalled),
          // - the current eva has at least on bundle specified
          // (if no bundles are specified, an eva is attached to all bundles),
          // - the current eva is specifically targeted for removal
          // (i.e., before deleting the display).
          if ($remove_all || !empty($eva['bundles']) || ($eva_field_name == $remove_one)) {
            // Does the eva exist in this display config?
            if (array_key_exists($eva_field_name, $config_data['content'])) {
              // Remove the eva if one of these is true:
              // - all evas should be removed,
              // - the eva does not list the entity's bundle (any more),
              // - the eva is specifically targeted for removal.
              if ($remove_all || !in_array($config_data['bundle'], $eva['bundles']) || ($eva_field_name == $remove_one)) {
                unset($config_data['content'][$eva_field_name]);
                // Exposed filter, too, if it's there.
                unset($config_data['content'][$eva_field_name . '_form']);
                $config->setData($config_data);
                $config->save();
              }
            }
          }
        }
      }
    }
  }
}