<?php
/* --------------------------------------------------------------
   GambioStoreResponseFacade.php 2020-05-15
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreResponseFacade_included')) {
        
        define('GambioStoreResponseFacade_included', true);
        
        /**
         * Class GambioStoreResponseFacade
         */
        class GambioStoreResponseFacade
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
             * GambioStoreResponseFacade constructor.
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
        }
    }
}
