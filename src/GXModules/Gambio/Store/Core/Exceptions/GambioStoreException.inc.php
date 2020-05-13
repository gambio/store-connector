<?php
/* --------------------------------------------------------------
   GambioStoreException.inc.php 2020-05-13
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

class GambioStoreException extends Exception
{
    /**
     * @var array
     */
    private $context;
    
    
    /**
     * GambioStoreException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param array           $context
     * @param \Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, $context = [], $previous = null)
    {
        $this->context = array_merge($context, [
            'exception' => static::class,
            'message'   => $message
        ]);
    
        parent::__construct($message, $code, $previous);
    }
    
    
    /**
     * Returns the context array.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
