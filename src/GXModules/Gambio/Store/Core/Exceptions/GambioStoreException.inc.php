<?php
/* --------------------------------------------------------------
   GambioStoreException.inc.php 2020-05-13
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStoreException
 */
abstract class GambioStoreException extends Exception
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
            'exception' => static::class
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
