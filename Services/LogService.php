<?php
namespace MultisiteContentCopier\Services;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MultisiteContentCopier\Utilities\PathUtility;

/**
 * Class LogService
 * @package Theme\Modules\Common\Services
 */
class LogService {

    /**
     * Contains an instance of Logger
     *
     * @var \Monolog\Logger
     */
    protected $logger = null;

    /**
     * @var string
     */
    protected $log_channel;

    /**
     * LogService constructor.
     *
     * @param string $log_channel
     *
     * @throws \Exception
     */
    public function __construct(
        string $log_channel
    ) {
        $this->log_channel = $log_channel;
        $this->logger      = new \Monolog\Logger( $this->log_channel );
        $log_files_path    = realpath( PathUtility::get_log_dir_path() );

        $this->logger->pushHandler( new StreamHandler( $log_files_path . '/app-debug.log',
            \Monolog\Logger::DEBUG, true ) );
        $this->logger->pushHandler( new StreamHandler( $log_files_path . '/app-error.log',
            \Monolog\Logger::ERROR, true ) );

        register_shutdown_function( [ $this, 'check_for_fatal' ] );
    }

    /**
     * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
     */
    public function check_for_fatal() {
        $error = error_get_last();

        if ( $error['type'] == E_ERROR ) {
            $this->get_logger()->critical( 'PHP Fatal Error', [
                'type'    => $error['type'],
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ] );
        }
    }

    /**
     * Returns a configured instance of \Monolog\Logger
     *
     * @return Logger
     */
    public function get_logger(): Logger {
        return $this->logger;
    }
}


