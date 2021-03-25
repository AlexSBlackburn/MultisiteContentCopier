<?php
namespace MultisiteContentCopier\Controllers;

use Monolog\Logger;
use MultisiteContentCopier\Common\DependencyContainer;
use MultisiteContentCopier\Exceptions\DependencyNotFoundException;
use MultisiteContentCopier\Services\ViewService;
use MultisiteContentCopier\Utilities\MultisiteUtility;

/**
 * Class SettingsController
 *
 * @package MultisiteContentCopier\Controllers
 */
class SettingsController {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $result;

    /**
     * @var ViewService
     */
    private $view_service;

    /**
     * SettingsController constructor
     *
     * @param Logger $logger
     * @param string $result
     *
     * @throws DependencyNotFoundException
     */
    public function __construct( Logger $logger, string $result = '' ) {
        $this->logger       = $logger;
        $this->result       = $result;
        $this->view_service = $this->get_view_service();

        add_action( 'network_admin_menu', [ $this, 'add_admin_menu' ] );
    }

    /**
     * @return ViewService
     *
     * @throws DependencyNotFoundException
     */
    private function get_view_service(): ViewService {
        return DependencyContainer::get( ViewService::class );
    }

    /**
     * Add the Content copier menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'Multisite content copier',
            'Content copier',
            'manage_network',
            'multisite_content_copier',
            [ $this, 'render_content_copier_page' ],
            'dashicons-controls-repeat'
        );
    }

    /**
     * Add the Content copier page
     */
    public function render_content_copier_page() {
        if ( ! empty( $this->result ) ) {
            $this->logger->debug( 'Rendering result page' );

            $this->view_service->load_view( 'content_copier_result_page.php' );
        } else {
            $this->logger->debug( 'Rendering form page' );

            $this->view_service->load_view( 'content_copier_form_page.php', [
                'sites' => MultisiteUtility::get_sites(),
                'sub_sites' => MultisiteUtility::get_sub_sites(),
            ] );
        }
    }

}
