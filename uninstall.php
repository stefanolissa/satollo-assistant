<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('assistant_settings');
delete_option('assistant_update_data');
delete_option('assistant_secret');
delete_option('assistant_version');

wp_unschedule_hook('assistant_clean_logs');

// No need to remove the cache folder bacause it will be deleted with the plugin