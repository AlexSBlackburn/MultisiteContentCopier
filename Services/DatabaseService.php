<?php
namespace MultisiteContentCopier\Services;

use Monolog\Logger;
use MultisiteContentCopier\Exceptions\DatabaseServiceException;
use MultisiteContentCopier\Utilities\MultisiteUtility;

/**
 * Class DatabaseService
 *
 * @package MultisiteContentCopier\Service
 */
class DatabaseService {

    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $site_prefixes = [];

    /**
     * DatabaseService constructor
     *
     * @param Logger $logger
     */
    public function __construct( Logger $logger ) {
        global $wpdb;

        $this->wpdb   = $wpdb;
        $this->logger = $logger;
    }

    /**
     * Get all tables of a sub-site
     *
     * @param int $site_id
     *
     * @return array
     *
     * @throws DatabaseServiceException
     */
    public function get_all_subsite_tables( int $site_id ) {
        $this->logger->debug( 'Fetching all sub-site tables for site ID ' . $site_id );

        $tables  = [];
        $results = $this->query( "SELECT table_name FROM information_schema.tables WHERE table_name LIKE '" . $this->get_site_prefix( $site_id ) . "%';" );

        if ( ! empty( $results ) ) {
            foreach ( $results as $result ) {
                $tables[] = $result->table_name;
            }

            $this->logger->debug( count( $tables ) . ' tables found:', [
                'tables' => $tables,
            ] );
        } else {
            $this->logger->debug( 'No tables found for site ID ' . $site_id );
        }

        return $tables;
    }

    /**
     * Perform an SQL query
     *
     * @param $sql_query
     *
     * @return mixed
     * @throws DatabaseServiceException
     */
    private function query( $sql_query ) {
        $result = $this->wpdb->get_results( $sql_query );

        if ( is_null( $result ) ) {
            throw new DatabaseServiceException( 'Database query returned null', $sql_query );
        }

        return $result;
    }

    /**
     * Get the database prefix by site ID
     *
     * @param int $site_id
     *
     * @return string
     */
    public function get_site_prefix( int $site_id ): string {
        if ( ! isset( $this->site_prefixes[ $site_id ] ) ) {
            $this->site_prefixes[ $site_id ] = $this->wpdb->get_blog_prefix( $site_id );
        }

        return $this->site_prefixes[ $site_id ];
    }

    /**
     * Get column names of a database table
     *
     * @param string $table
     *
     * @return array
     *
     * @throws DatabaseServiceException
     */
    public function get_table_columns( string $table ) {
        $columns = [];
        $results = $this->query( 'DESCRIBE ' . $table );

        if ( ! empty( $results ) ) {
            foreach ( $results as $result ) {
                $columns[ $result->Field ] = [
                    'name' => $result->Field,
                    'type' => $result->Type,
                ];
            }
        }

        return $columns;
    }

    /**
     * Add column to database table
     *
     * @param array  $column
     * @param string $table
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function add_column( array $column, string $table ) {
        return $this->query( 'ALTER TABLE ' . $table . ' ADD ' . $column['name'] . ' ' . $column['type'] );
    }

    /**
     * Delete column from database table
     *
     * @param array  $column
     * @param string $table
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function delete_column( array $column, string $table ) {
        return $this->query( 'ALTER TABLE ' . $table . ' DROP COLUMN ' . $column['name'] );
    }

    /**
     * Delete all rows from a table
     *
     * @param string $table
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function delete_table_rows( string $table ) {
        $this->logger->debug( 'Deleting rows of table ' . $table );

        return $this->query( 'DELETE FROM ' . $table );
    }

    /**
     * Delete a row from the options table
     *
     * @param string $option_id
     * @param int    $site_id
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function delete_option_row( string $option_id, int $site_id ) {
        return $this->query( 'DELETE FROM ' . $this->get_site_prefix( $site_id ) . 'options WHERE option_id = ' . $option_id );
    }

    /**
     * Insert a row from the parent site options table into a sub-site options table
     *
     * @param string $option_id
     * @param int    $site_id
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function insert_option_row( string $option_id, int $site_id ) {
        return $this->query( 'INSERT INTO ' . $this->get_site_prefix( $site_id ) . 'options SELECT * FROM ' . $this->get_main_site_prefix() . 'options WHERE option_id = ' . $option_id );
    }

    /**
     * Get the database prefix for the main site
     *
     * @return string
     */
    public function get_main_site_prefix() {
        return $this->get_site_prefix( MultisiteUtility::get_main_site_id() );
    }

    /**
     * Insert the contents of one table into another
     *
     * @param string $old_table
     * @param string $new_table
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function insert_table_contents( string $old_table, string $new_table ) {
        $this->logger->debug( 'Inserting contents of table "' . $new_table . '" into table "' . $old_table . '"' );

        return $this->query( 'INSERT INTO ' . $old_table . ' SELECT * FROM ' . $new_table );
    }

    /**
     * Replace a string in all rows in a column of a table
     *
     * @param string $string
     * @param string $replacement
     * @param string $column
     * @param string $table
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function replace_string_in_column( string $string, string $replacement, string $column, string $table ) {
        $this->logger->debug( 'Replacing string "' . $string . '" with "' . $replacement . '" in column "' . $column . '" of table "' . $table . '"' );

        return $this->query( "UPDATE " . $table . " SET " . $column . " = REPLACE(" . $column . ", '" . $string . "', '" . $replacement . "')" );
    }

    /**
     * @param int $site_id
     *
     * @return mixed
     *
     * @throws DatabaseServiceException
     */
    public function get_options_table_rows( int $site_id ) {
        $this->logger->debug( 'Fetching rows from options table for site ID ' . $site_id );

        return $this->query( 'SELECT * FROM ' . $this->get_site_prefix( $site_id ) . 'options' );
    }

}
