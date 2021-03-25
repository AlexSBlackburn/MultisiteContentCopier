<?php
namespace MultisiteContentCopier\Utilities;

/**
 * Class PathUtility
 *
 * @package MultisiteContentCopier\Utilities
 */
class PathUtility {

    /**
     * @var string
     */
    private static $plugin_root_dir_path;

    /**
     * Get the full path to the views directory
     *
     * @return string
     */
    public static function get_views_dir_path(): string {
        return static::get_plugin_root_dir_path() . 'views/';
    }

    /**
     * Get the full path to the plugin root directory
     *
     * @return string
     */
    private static function get_plugin_root_dir_path(): string {
        if ( is_null( static::$plugin_root_dir_path ) ) {
            $plugin_relative_path = plugin_basename( __FILE__ );
            $relative_path_parts  = explode( '/', $plugin_relative_path );

            static::set_plugin_root_dir_path( str_replace( $plugin_relative_path, trailingslashit( $relative_path_parts[0] ), __FILE__ ) );
        }

        return static::$plugin_root_dir_path;
    }

    /**
     * @param string $path
     */
    private static function set_plugin_root_dir_path( string $path ) {
        static::$plugin_root_dir_path = $path;
    }

    /**
     * Get the full path to the logs directory
     *
     * @return string
     */
    public static function get_log_dir_path(): string {
        return static::get_plugin_root_dir_path() . 'logs/';
    }

}