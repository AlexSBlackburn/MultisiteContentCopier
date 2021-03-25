<?php
namespace MultisiteContentCopier;

use Monolog\Logger;
use MultisiteContentCopier\Common\DependencyContainer;
use MultisiteContentCopier\Controllers\ContentCopierController;
use MultisiteContentCopier\Controllers\SettingsController;
use MultisiteContentCopier\Exceptions\DependencyNotFoundException;

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Class MultisiteContentCopier
 *
 * Plugin Name: Multisite Content Copier
 * Plugin URI: https://github.com/AlexSBlackburn/MultisiteContentCopier
 * Description: Allows quick and easy duplication of main site content to a newly created sub-site
 * Version: 0.1.4
 * Author: Alex Blackburn
 * License: MIT License
 *
 * @package MultisiteContentCopier
 */
class MultisiteContentCopier {

    private $logger;

    /**
     * MultisiteContentCopier constructor
     */
    public function __construct() {
        if ( is_multisite() && is_admin() ) {
            include_once 'functions.php';
            include_once 'vendor/autoload.php';

            try {
                $this->logger = $this->get_logger();

                add_action( 'plugins_loaded', [ $this, 'setup' ] );
            } catch ( DependencyNotFoundException $e ) {
                die( 'Dependency not found error: ' . $e->getMessage() );
            }
        }
    }

    /**
     * @return Logger
     *
     * @throws DependencyNotFoundException
     */
    private function get_logger(): Logger {
        return DependencyContainer::get( Logger::class );
    }

    /**
     * Setup plugin once loaded
     *
     * @throws DependencyNotFoundException
     * @throws Exceptions\DatabaseServiceException
     */
    public function setup() {
        $result = '';

        if ( isset( $_POST['rk_wp_copy_content'] ) && isset( $_POST['site_from'] ) && isset( $_POST['sites_to'] ) ) {
            $this->logger->debug( 'Form submit detected.' );
            $result = true;
            new ContentCopierController( $this->logger );
        }

        new SettingsController( $this->logger, $result );
    }
}

new MultisiteContentCopier();
