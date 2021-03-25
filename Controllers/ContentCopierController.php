<?php
namespace MultisiteContentCopier\Controllers;

use Monolog\Logger;
use MultisiteContentCopier\Common\Config;
use MultisiteContentCopier\Common\DependencyContainer;
use MultisiteContentCopier\Exceptions\DatabaseServiceException;
use MultisiteContentCopier\Exceptions\DependencyNotFoundException;
use MultisiteContentCopier\Services\ContentCopyService;
use MultisiteContentCopier\Services\DatabaseService;
use MultisiteContentCopier\Services\UploadsCopyService;

/**
 * Class ContentCopierController
 *
 * @package MultisiteContentCopier\Controllers
 */
class ContentCopierController {

    /**
     * @var Logger
     */
    private static $logger;

    /**
     * @var ContentCopyService
     */
    private static $content_copy_service;

    /**
     * @var UploadsCopyService
     */
    private static $uploads_copy_service;

    /**
     * @var DatabaseService
     */
    private static $database_service;

    /**
     * ContentCopierController constructor.
     *
     * @param Logger $logger
     *
     * @throws DatabaseServiceException
     * @throws DependencyNotFoundException
     */
    public function __construct( Logger $logger ) {
        static::$logger           = $logger;
        static::$database_service = static::get_database_service();
        $site_from                = (int) $_POST['site_from'];
        $sites_to                 = [];

        foreach ( $_POST['sites_to'] as $key => $value ) {
            $sites_to[ (int) $key ] = static::$database_service->get_all_subsite_tables( (int) $key );
        }

        $sites = [
            'from' => $site_from,
            'to'   => $sites_to,
        ];

        update_network_option( null, Config::SITES_OPTION_NAME, $sites );
        update_network_option( null, Config::DELETE_UPLOADS_OPTION_NAME, $sites );
        update_network_option( null, Config::UPLOADS_OPTION_NAME, $sites );

        static::$logger->debug( 'Sites submitted:', [
            'sites' => $sites,
        ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_content_copier_js' ] );
    }

    /**
     * @param string $hook
     */
    public function enqueue_content_copier_js( string $hook ) {
        if ( $hook === 'toplevel_page_multisite_content_copier' ) {
            wp_enqueue_script( 'multisite_content_copier', plugins_url( 'src/js/content_copier.js', __DIR__ ), [ 'jquery' ] );
            wp_localize_script( 'multisite_content_copier', 'ajax', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
        }
    }

    /**
     * @return Logger
     *
     * @throws DependencyNotFoundException
     */
    private static function get_logger(): Logger {
        return DependencyContainer::get( Logger::class );
    }

    /**
     * @return ContentCopyService
     *
     * @throws DependencyNotFoundException
     */
    private static function get_content_copy_service(): ContentCopyService {
        return DependencyContainer::get( ContentCopyService::class );
    }

    /**
     * @return UploadsCopyService
     *
     * @throws DependencyNotFoundException
     */
    private static function get_uploads_copy_service(): UploadsCopyService {
        return DependencyContainer::get( UploadsCopyService::class );
    }

    /**
     * @return DatabaseService
     *
     * @throws DependencyNotFoundException
     */
    private static function get_database_service(): DatabaseService {
        return DependencyContainer::get( DatabaseService::class );
    }

    /**
     * @return bool|string
     * @throws DependencyNotFoundException
     */
    public static function copy_table() {
        static::$logger               = static::get_logger();
        static::$content_copy_service = static::get_content_copy_service();
        static::$database_service     = static::get_database_service();
        $sites                        = get_network_option( null, Config::SITES_OPTION_NAME );

        if ( empty( $sites['to'] ) ) {
            update_network_option( null, Config::SITES_OPTION_NAME, [] );

            return false;
        }

        foreach ( $sites['to'] as $site_id => $site_tables ) {
            if ( ! empty( $site_tables ) ) {
                $table = $site_tables[0];
                unset( $site_tables[0] );
                $sites['to'][ $site_id ] = array_values( $site_tables );
                update_network_option( null, Config::SITES_OPTION_NAME, $sites );

                return self::copy_table_to_site( $sites['from'], $site_id, $table );
            } else {
                unset( $sites['to'][ $site_id ] );
            }
        }

        return false;
    }

    /**
     * Copy content to site
     *
     * @param int    $from_site_id
     * @param int    $to_site_id
     * @param string $site_table
     *
     * @return string
     */
    private static function copy_table_to_site( int $from_site_id, int $to_site_id, string $site_table ) {
        $table_name     = str_replace( static::$database_service->get_site_prefix( $to_site_id ), static::$database_service->get_site_prefix( $from_site_id ), $site_table );
        $from_site_info = get_blog_details( $from_site_id );
        $to_site_info   = get_blog_details( $to_site_id );
        static::$logger->debug( 'Starting copying of table "' . $table_name . '" from site "' . $from_site_info->blogname . '" (ID: ' . $from_site_id . ') to site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ')' );

        try {
            $result = static::$content_copy_service->copy_table_to_site( $from_site_id, $to_site_id, $site_table );

            if ( $result ) {
                $message = 'Table "' . $table_name . '" successfully copied from site "' . $from_site_info->blogname . '" (ID: ' . $from_site_id . ') to site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ')';
            } else {
                $message = 'Table "' . $table_name . '" failed to copy from site "' . $from_site_info->blogname . '" (ID: ' . $from_site_id . ') to site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ') - check the debug log.';
            }
        } catch ( DatabaseServiceException $e ) {
            static::$logger->debug( 'Database error during operation', [
                'query' => $e->get_query(),
            ] );
            static::$logger->error( 'Database error during operation', [
                'query' => $e->get_query(),
            ] );

            return 'There was an error while making changes to the database. The query being run was: ' . $e->get_query();
        }

        return $message;
    }

    /**
     * @return bool|string
     * @throws DependencyNotFoundException
     */
    public static function delete_uploads() {
        static::$logger               = static::get_logger();
        static::$uploads_copy_service = static::get_uploads_copy_service();
        $sites                        = get_network_option( null, Config::DELETE_UPLOADS_OPTION_NAME );

        if ( empty( $sites ) ) {
            update_network_option( null, Config::DELETE_UPLOADS_OPTION_NAME, [] );

            return false;
        }

        foreach ( $sites['to'] as $to_site_id => $site_tables ) {
            unset( $sites['to'][ $to_site_id ] );
            update_network_option( null, Config::DELETE_UPLOADS_OPTION_NAME, $sites );

            return self::delete_uploads_from_site( $to_site_id );
        }

        return false;
    }

    /**
     * @return bool|string
     * @throws DependencyNotFoundException
     */
    public static function copy_uploads() {
        static::$logger               = static::get_logger();
        static::$uploads_copy_service = static::get_uploads_copy_service();
        $sites                        = get_network_option( null, Config::UPLOADS_OPTION_NAME );

        if ( empty( $sites ) ) {
            update_network_option( null, Config::UPLOADS_OPTION_NAME, [] );

            return false;
        }

        foreach ( $sites['to'] as $to_site_id => $site_tables ) {
            unset( $sites['to'][ $to_site_id ] );
            update_network_option( null, Config::UPLOADS_OPTION_NAME, $sites );

            return self::copy_uploads_to_site( $sites['from'], $to_site_id );
        }

        return false;
    }

    /**
     * Delete existing uploads
     *
     * @param int $to_site_id
     *
     * @return string
     */
    private static function delete_uploads_from_site( int $to_site_id ) {
        $to_site_info = get_blog_details( $to_site_id );
        static::$logger->debug( 'Starting deleting old uploads from site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ')' );
        $result = static::$uploads_copy_service->delete_uploads_from_site( $to_site_id );

        if ( $result ) {
            $message = 'Old uploads successfully deleted from site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ').';
        } else {
            $message = 'There was an error deleting old uploads from site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ') - check the debug log.';
        }

        return $message;
    }

    /**
     * Copy uploads to site
     *
     * @param int $from_site_id
     * @param int $to_site_id
     *
     * @return string
     */
    private static function copy_uploads_to_site( int $from_site_id, int $to_site_id ) {
        $from_site_info = get_blog_details( $from_site_id );
        $to_site_info   = get_blog_details( $to_site_id );
        static::$logger->debug( 'Starting uploads copying from site "' . $from_site_info->blogname . '" (ID: ' . $from_site_id . ') to site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ')' );
        $result = static::$uploads_copy_service->copy_uploads_to_site( $from_site_id, $to_site_id );

        if ( $result ) {
            $message = 'Uploads successfully copied from site "' . $from_site_info->blogname . '" (ID: ' . $from_site_id . ') to site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ').';
        } else {
            $message = 'There was an error copying uploads from site "' . $from_site_info->blogname . '" (ID: ' . $from_site_id . ') to site "' . $to_site_info->blogname . '" (ID: ' . $to_site_id . ') - check the debug log.';
        }

        return $message;
    }

}
