<?php
/* --------------------------------------------------------------
   WrongFilePermissionException.inc.php 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

class WrongFilePermissionException extends Exception
{
    /**
     * @var array
     */
    private $content;
    
    
    /**
     * WrongFilePermissionException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     * @param array           $content
     */
    public function __construct(
        $message = "",
        $code = 0,
        Throwable $previous = null,
        array $content = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->content = $content;
    }
    
    
    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }
}