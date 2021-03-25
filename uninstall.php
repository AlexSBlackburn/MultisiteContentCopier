<?php
/**
 * Cleanup before plugin uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die();
}

require_once 'Common/Config.php';

delete_network_option( null, \MultisiteContentCopier\Common\Config::SITES_OPTION_NAME );
delete_network_option( null, \MultisiteContentCopier\Common\Config::DELETE_UPLOADS_OPTION_NAME );
delete_network_option( null, \MultisiteContentCopier\Common\Config::UPLOADS_OPTION_NAME );