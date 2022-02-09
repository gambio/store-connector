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
         *
         * This class is the facade for the GambioStoreResponse class.
         *
         * The class is used for migration scripts that can be run after an installation or uninstallation of a package
         * through the Store. It is not included in our Connector Core logic and has a define constant to prevent it
         * from being loaded until desired. This was introduced to ensure that during a self update of the Connector,
         * the class will have been replaced with its updated counter part before being loaded into PHP memory,
         * allowing us to execute new code during the self update.
         *
         * Functionality is implemented by duplicating methods of the original class.
         *
         * The initial check for the StoreKey_MigrationScript constant avoids automatic class auto-loading
         * by the shop's "MainFactory" since we need a unique new version during the update.
         *
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
            
            
            /**
             * Returns the status code.
             *
             * @return array|null
             */
            public function getStatus()
            {
                return $this->getInformation('http_code');
            }
            
            
        }
    }
}
