<?php
/* --------------------------------------------------------------
   AbstractGambioStoreController.inc.php 2020-09-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../../GambioStoreConnector.inc.php';

abstract class AbstractGambioStoreController extends AdminHttpViewController
{
    /**
     * Gets the auth headers
     *
     * @var \GambioStoreConfiguration $configuration
     * 
     * @return array
     */
    protected static function getGambioStoreAuthHeaders($configuration)
    {
        return [
            'X-ACCESS-TOKEN' => $configuration->get('GAMBIO_STORE_ACCESS_TOKEN')
        ];
    }
    
    
    /**
     * Gets the store api URL
     * 
     * @var \GambioStoreConfiguration $configuration
     *                                              
     * @return string
     */
    protected static function getGambioStoreApiUrl($configuration)
    {
        $gambioUrl = $configuration->get('GAMBIO_STORE_API_URL');
        
        // Fall back to the production Gambio Store api URL if none is set.
        if (empty($gambioUrl)) {
            $gambioUrl = 'https://store.gambio.com';
            $configuration->set('GAMBIO_STORE_API_URL', $gambioUrl);
        }
        
        return $gambioUrl;
    }
    
    
    /**
     * Gets the store token
     *
     * @var \GambioStoreConfiguration $configuration
     * @var \GambioStoreConnector $connector
     * 
     * @return mixed
     */
    protected static function getGambioStoreToken($configuration, $connector)
    {
        $gambioStoreToken = $configuration->get('GAMBIO_STORE_TOKEN');
        if (empty($gambioStoreToken)) {
            $gambioStoreToken = $connector->generateToken();
            $configuration->set('GAMBIO_STORE_TOKEN', $gambioStoreToken);
        }
        
        return $gambioStoreToken;
    } 
}
