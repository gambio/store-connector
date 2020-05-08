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

/**
 * Class WrongFilePermissionException
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
     * @param array           $content
     * @param \Exception|null $previous
     */
    public function __construct(
        $message = "",
        array $content = [],
        Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
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