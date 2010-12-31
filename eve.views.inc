<?php

/**
 * Implements hook_views_plugins().
 */
function eve_views_plugins() {
  return array(
    'display' => array(
      'entity_view' => array(
        'title' => t('Entity content'),
        'help' => t("Display the view as a part of an entity's content."),
        'handler' => 'eve_plugin_display_entity',
        'theme' => 'eve_display_entity_view',
        'theme file' => 'eve.theme.inc',
        'uses hook entity view' => TRUE,
        'use ajax' => TRUE,
        'use pager' => TRUE,
        'use more' => TRUE,
        'accept attachments' => TRUE,
        'admin' => t('Entity content'),
        'help topic' => 'display-entity',
      ),
    ),
  );
}
