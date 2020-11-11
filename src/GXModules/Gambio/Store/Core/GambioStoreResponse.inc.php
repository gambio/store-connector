<?php
/* --------------------------------------------------------------
   GambioStoreResponse.php 2020-05-15
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStoreResponse
 */
class GambioStoreResponse
{
    /**
     * @var string
     */
    private $body;
    
    /**
     * @var array
     */
    private $headers;
    
    /**
     * @var array
     */
    private $information;
    
    
    /**
     * GambioStoreResponse constructor.
     *
     * @param       $body
     * @param array $headers
     * @param array $information
     */
    public function __construct($body, array $headers, array $information)
    {
        $this->body        = $body;
        $this->information = $information;
        $this->headers     = $headers;
    }
    
    
    /**
     * Returns the response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    
    
    /**
     * Returns the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    
    /**
     * Returns the header if present otherwise null
     *
     * @param $key
     *
     * @return string
     */
    public function getHeader($key)
    {
        $key = strtoupper($key);
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        
        return null;
    }
    
    
    /**
     * Returns the status code.
     *
     * @return array|null
     */
    public function getStatus()
    {
        return $this->getInformation('http_code');
    }
    
    
    /**
     * Returns the response information
     *
     * @param null $key
     *
     * @return array|null
     */
    public function getInformation($key = null)
    {
        if ($key === null) {
            return $this->information;
        }
        
        if (isset($this->information[$key])) {
            return $this->information[$key];
        }
        
        return null;
    }
}
