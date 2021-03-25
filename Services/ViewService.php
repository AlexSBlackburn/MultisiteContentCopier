<?php
namespace MultisiteContentCopier\Services;

use MultisiteContentCopier\Utilities\PathUtility;

/**
 * Class ViewService
 *
 * @package MultisiteContentCopier\Services
 */
class ViewService {

    /**
     * Load template files
     *
     * @param string $view_path
     * @param array  $view_variables
     */
    public function load_view( string $view_path, array $view_variables = [] ) {
        extract( $view_variables );

        if ( $path = realpath( PathUtility::get_views_dir_path() . $view_path ) ) {
            include_once $path;
        } else {
            echo 'Error: Could not locate the ' . $view_path . ' view. Path: ' . $path;
        }
    }

}