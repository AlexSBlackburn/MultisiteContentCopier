<?php
namespace MultisiteContentCopier\Services;

use Monolog\Logger;
use MultisiteContentCopier\Utilities\MultisiteUtility;

/**
 * Class UploadsCopyService
 *
 * @package MultisiteContentCopier\Services
 */
class UploadsCopyService {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * UploadsCopyService constructor.
     *
     * @param Logger $logger
     */
    public function __construct( Logger $logger ) {
        $this->logger = $logger;
    }

    /**
     * @param int $to_site_id
     *
     * @return bool
     */
    public function delete_uploads_from_site( int $to_site_id ) {
        $uploads_dir         = wp_upload_dir()['basedir'];
        $to_site_uploads_dir = $uploads_dir . '/sites/' . $to_site_id;
        $this->logger->debug( 'Deleting directory "' . $to_site_uploads_dir . '"' );
        $result = $this->remove_files( $to_site_uploads_dir );
        wp_mkdir_p( $to_site_uploads_dir );

        return $result;
    }

    /**
     * @param int $from_site_id
     * @param int $to_site_id
     *
     * @return bool
     */
    public function copy_uploads_to_site( int $from_site_id, int $to_site_id ) {
        $uploads_dir           = wp_upload_dir()['basedir'];
        $from_site_uploads_dir = $from_site_id === MultisiteUtility::get_main_site_id() ? $uploads_dir : $uploads_dir . '/sites/' . $from_site_id;
        $to_site_uploads_dir   = $uploads_dir . '/sites/' . $to_site_id;
        $this->logger->debug( 'Beginning uploads copy from "' . $from_site_uploads_dir . '" to "' . $to_site_uploads_dir . '"' );

        return $this->copy_files( $from_site_uploads_dir, $to_site_uploads_dir, [ 'sites' ] );
    }

    /**
     * Remove all files and directories recursively in a given path
     *
     * @param string $source
     * @param array  $excludes
     *
     * @return bool
     */
    private function remove_files( string $source, array $excludes = [] ) {
        $excludes = array_merge( [ '.', '..' ], $excludes );

        if ( is_dir( $source ) ) {
            $files = array_diff( scandir( $source ), $excludes );

            foreach ( $files as $file ) {
                $result = $this->remove_files( $source . '/' . $file );

                if ( $result === false ) {
                    return $result;
                }
            }

            return rmdir( $source );
        } elseif ( file_exists( $source ) ) {
            return unlink( $source );
        }

        return true;
    }

    /**
     * Recursively copy files from source to destination
     * Third parameter excludes directories or files
     *
     * @param string $source
     * @param string $destination
     * @param array  $excludes
     *
     * @return bool
     */
    private function copy_files( string $source, string $destination, array $excludes = [] ) {
        $excludes = array_merge( [ '.', '..' ], $excludes );

        if ( is_dir( $source ) ) {
            wp_mkdir_p( $destination );

            // Get all files and directories in current directory
            $files = array_diff( scandir( $source ), $excludes );

            foreach ( $files as $file ) {
                // Recursive copy
                $result = $this->copy_files( $source . '/' . $file, $destination . '/' . $file );

                if ( $result === false ) {
                    $this->logger->error( 'Error while copying "' . $source . '/' . $file . '" to "' . $destination . '/' . $file . '"' );

                    return $result;
                }
            }

            return true;
        } elseif ( file_exists( $source ) ) {
            $result = copy( $source, $destination );

            if ( $result === false ) {
                $this->logger->error( 'Error while copying file "' . $source . '" to "' . $destination . '"' );
            }

            return $result;
        }

        return true;
    }

}
