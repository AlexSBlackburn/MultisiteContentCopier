<?php
namespace MultisiteContentCopier\Services;

use Monolog\Logger;
use MultisiteContentCopier\Common\DependencyContainer;
use MultisiteContentCopier\Exceptions\DatabaseServiceException;
use MultisiteContentCopier\Exceptions\DependencyNotFoundException;
use MultisiteContentCopier\Utilities\MultisiteUtility;

/**
 * Class ContentCopyService
 *
 * @package MultisiteContentCopier\Services
 */
class ContentCopyService {

    const PRESERVE_OPTION_ROWS = [
        'siteurl',
        'home',
        'blogname',
        'admin_email',
        'WPLANG',
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DatabaseService
     */
    private $database_service;

    /**
     * ContentCopyService constructor
     *
     * @param Logger $logger
     *
     * @throws DependencyNotFoundException
     */
    public function __construct( Logger $logger ) {
        $this->logger           = $logger;
        $this->database_service = $this->get_database_service();
    }

    /**
     * @return DatabaseService
     *
     * @throws DependencyNotFoundException
     */
    private function get_database_service(): DatabaseService {
        return DependencyContainer::get( DatabaseService::class );
    }

    /**
     * @param int    $from_site_id
     * @param int    $to_site_id
     * @param string $to_site_table
     *
     * @return bool
     * @throws DatabaseServiceException
     */
    public function copy_table_to_site( int $from_site_id, int $to_site_id, string $to_site_table ) {
        $from_site = MultisiteUtility::get_site( $from_site_id );
        $sub_site  = MultisiteUtility::get_site( $to_site_id );

        if ( $to_site_table === $this->database_service->get_site_prefix( $to_site_id ) . 'options' ) {
            // Replace all option table rows except site specific data
            $this->replace_options( $from_site_id, $to_site_id );

            return true;
        }

        $from_site_table = str_replace(
            $this->database_service->get_site_prefix( $to_site_id ),
            $this->database_service->get_site_prefix( $from_site_id ),
            $to_site_table
        );

        $this->logger->debug( 'Overwriting content for table ' . $to_site_table . ' with table ' . $from_site_table );

        // Delete all existing rows
        $this->database_service->delete_table_rows( $to_site_table );

        // Make sure columns are the same
        $this->fix_columns( $to_site_table, $from_site_table );

        // Insert the from site content
        $this->database_service->insert_table_contents( $to_site_table, $from_site_table );

        $columns = $this->database_service->get_table_columns( $to_site_table );

        foreach ( $columns as $column ) {
            // Fix URLs
            $this->database_service->replace_string_in_column( $from_site['domain'], $sub_site['domain'], $column['name'], $to_site_table );

            // Fix uploads path
            $from_uploads_path = $from_site_id === MultisiteUtility::get_main_site_id() ? '/uploads/' : '/uploads/sites/' . $from_site_id . '/';
            $this->database_service->replace_string_in_column( $from_uploads_path, '/uploads/sites/' . $to_site_id . '/', $column['name'], $to_site_table );
        }

        return true;
    }

    /**
     * Ensure to site and from site tables have the same columns
     *
     * @param string $to_site_table
     * @param string $from_site_table
     *
     * @throws DatabaseServiceException
     */
    private function fix_columns( string $to_site_table, string $from_site_table ) {
        $this->logger->debug( 'Checking missing columns for table ' . $to_site_table );

        $to_site_table_columns    = $this->database_service->get_table_columns( $to_site_table );
        $from_site_table_columns  = $this->database_service->get_table_columns( $from_site_table );
        $missing_sub_site_columns = array_diff( array_keys( $from_site_table_columns ), array_keys( $to_site_table_columns ) );

        if ( ! empty( $missing_sub_site_columns ) ) {
            $this->logger->debug( 'Missing columns in sub-site table ' . $to_site_table, [
                'columns' => $missing_sub_site_columns,
            ] );

            foreach ( $missing_sub_site_columns as $column_name ) {
                $this->database_service->add_column( $from_site_table_columns[ $column_name ], $to_site_table );
            }
        }

        $missing_main_site_columns = array_diff( array_keys( $to_site_table_columns ), array_keys( $from_site_table_columns ) );

        if ( ! empty( $missing_main_site_columns ) ) {
            $this->logger->debug( 'Extra columns in sub-site table ' . $to_site_table, [
                'columns' => $missing_main_site_columns,
            ] );

            foreach ( $missing_main_site_columns as $column_name ) {
                $this->database_service->delete_column( $to_site_table_columns[ $column_name ], $to_site_table );
            }
        }
    }

    /**
     * Replace option rows for site
     *
     * @param int $from_site_id
     * @param int $to_site_id
     *
     * @throws DatabaseServiceException
     */
    private function replace_options( int $from_site_id, int $to_site_id ) {
        $this->logger->debug( 'Deleting to site option table rows' );

        $preserved_options = array_merge( self::PRESERVE_OPTION_ROWS, [
            $this->database_service->get_site_prefix( $to_site_id ) . 'user_roles',
            $this->database_service->get_site_prefix( $from_site_id ) . 'user_roles',
        ] );
        $options           = $this->database_service->get_options_table_rows( $to_site_id );

        if ( ! empty( $options ) ) {
            foreach ( $options as $option ) {
                if ( ! in_array( $option->option_name, $preserved_options ) ) {
                    $this->database_service->delete_option_row( $option->option_id, $to_site_id );
                }
            }
        }

        $this->logger->debug( 'Inserting from site option table rows into to site' );
        $from_site_options = $this->database_service->get_options_table_rows( $from_site_id );

        if ( ! empty( $from_site_options ) ) {
            foreach ( $from_site_options as $option ) {
                if ( ! in_array( $option->option_name, $preserved_options ) ) {
                    $this->database_service->insert_option_row( $option->option_id, $to_site_id );
                }
            }
        }
    }

}
