<?php
namespace MultisiteContentCopier\Utilities;

/**
 * Class MultisiteUtility
 *
 * @package MultisiteContentCopier\Utilities
 */
class MultisiteUtility {

    /**
     * @var array
     */
    private static $sites = [];

    /**
     * @var array
     */
    private static $sub_sites = [];

    /**
     * @var int
     */
    private static $main_site_id;

    /**
     * @var array
     */
    private static $main_site;

    /**
     * Get a site by ID
     *
     * @param int $site_id
     *
     * @return array|mixed|null
     */
    public static function get_site( int $site_id ) {
        if ( $site_id === static::get_main_site_id() ) {
            return static::get_main_site();
        }

        $sites = static::get_sites();

        if ( array_key_exists( $site_id, $sites ) ) {
            return $sites[ $site_id ];
        }

        return null;
    }

    /**
     * @return int
     */
    public static function get_main_site_id() {
        if ( is_null( static::$main_site_id ) ) {
            static::set_main_site_id( get_network()->site_id );
        }

        return static::$main_site_id;
    }

    /**
     * @param int $site_id
     */
    private static function set_main_site_id( int $site_id ) {
        static::$main_site_id = $site_id;
    }

    /**
     * @return array
     */
    public static function get_main_site() {
        if ( is_null( static::$main_site ) ) {
            $site_id      = static::get_main_site_id();
            $site_details = get_blog_details( $site_id );

            static::set_main_site( [
                'name'   => $site_details->blogname,
                'domain' => $site_details->domain,
            ] );
        }

        return static::$main_site;
    }

    /**
     * @param array $site
     */
    private static function set_main_site( array $site ) {
        static::$main_site = $site;
    }

    /**
     * @return array
     */
    public static function get_sites() {
        if ( empty( static::$sites ) ) {
            $sites    = [];
            $wp_sites = get_sites();

            if ( ! empty( $wp_sites ) ) {
                foreach ( $wp_sites as $site ) {
                    $site_details            = get_blog_details( $site->blog_id );
                    $sites[ $site->blog_id ] = [
                        'name'   => $site_details->blogname,
                        'domain' => $site_details->domain,
                    ];
                }
            }

            static::set_sites( $sites );
        }

        return static::$sites;
    }

    /**
     * @return array
     */
    public static function get_sub_sites() {
        if ( empty( static::$sub_sites ) ) {
            $sites    = [];
            $wp_sites = get_sites( [
                'site__not_in' => [ get_network()->site_id ],
            ] );

            if ( ! empty( $wp_sites ) ) {
                foreach ( $wp_sites as $site ) {
                    $site_details            = get_blog_details( $site->blog_id );
                    $sites[ $site->blog_id ] = [
                        'name'   => $site_details->blogname,
                        'domain' => $site_details->domain,
                    ];
                }
            }

            static::set_sub_sites( $sites );
        }

        return static::$sub_sites;
    }

    /**
     * @param array $sites
     */
    private static function set_sites( array $sites ) {
        static::$sites = $sites;
    }

    /**
     * @param array $sites
     */
    private static function set_sub_sites( array $sites ) {
        static::$sub_sites = $sites;
    }

}
