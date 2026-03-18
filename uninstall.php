<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('assistant_settings');
delete_option('assistant_update_data');
delete_option('assistant_secret');

// No need to remove the cache folder bacause it will be deleted with the plugin