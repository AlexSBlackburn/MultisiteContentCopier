<?php
namespace MultisiteContentCopier\Common;

use Monolog\Logger;
use Pimple\Container;
use MultisiteContentCopier\Exceptions\DependencyNotFoundException;
use MultisiteContentCopier\Services\ContentCopyService;
use MultisiteContentCopier\Services\DatabaseService;
use MultisiteContentCopier\Services\LogService;
use MultisiteContentCopier\Services\UploadsCopyService;
use MultisiteContentCopier\Services\ViewService;

/**
 * Class DependencyContainer
 *
 * @package ReviewImport\Common
 */
class DependencyContainer {

    /**
     * Contains an instance of Container
     *
     * @var \Pimple\Container
     */
    private static $container;

    /**
     * Retrieves a dependency from the Pimple Container
     *
     * @param string $dependency_slug
     *
     * @throws DependencyNotFoundException
     *
     * @return mixed
     */
    public static function get( string $dependency_slug ) {
        $container = static::get_container();

        if ( isset( $container[ $dependency_slug ] ) ) {
            return $container[ $dependency_slug ];
        }

        throw new DependencyNotFoundException( 'Dependency ' . $dependency_slug . ' was not found in the container' );
    }

    /**
     * Retrieves or instantiates an instance of the Pimple Container
     *
     * This instantiates classes when and only if we need them, including their dependencies
     *
     * @return Container
     */
    private static function get_container(): Container {
        if ( ! isset( static::$container ) ) {

            static::$container = new Container();

            static::$container[ Logger::class ] = function ( $c ) {
                $log_service = new LogService( 'multisite_content_copier' );

                return $log_service->get_logger();
            };

            static::$container[ ViewService::class ] = function ( $c ) {
                return new ViewService();
            };

            static::$container[ ContentCopyService::class ] = function ( $c ) {
                return new ContentCopyService( $c[ Logger::class ] );
            };

            static::$container[ UploadsCopyService::class ] = function ( $c ) {
                return new UploadsCopyService( $c[ Logger::class ] );
            };

            static::$container[ DatabaseService::class ] = function ( $c ) {
                return new DatabaseService( $c[ Logger::class ] );
            };
        }

        return static::$container;
    }

}
