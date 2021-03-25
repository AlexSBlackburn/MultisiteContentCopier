<?php
namespace MultisiteContentCopier\Exceptions;

/**
 * Class DatabaseServiceException
 *
 * @package MultisiteContentCopier\Exceptions
 */
class DatabaseServiceException extends \Exception {

    /**
     * @var string
     */
    private $query;

    /**
     * DatabaseServiceException constructor
     *
     * @param string $message
     * @param string $query
     */
    public function __construct( string $message = '', $query = '' ) {
        $this->query = $query;

        parent::__construct( $message );
    }

    /**
     * @return string
     */
    public function get_query() {
        return $this->query;
    }

}