<?php
/* --------------------------------------------------------------
   GambioStoreAuth.php 2020-09-22
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'Exceptions/GambioStoreRequestingAuthInvalidStatusException.inc.php';

/**
 * Class GambioStoreAuth
 *
 * Handles all actions related to auth with the Store in the Shop
 */
class GambioStoreAuth
{
    /**
     * @var \GambioStoreConfiguration
     */
    private $configuration;
    
    /**
     * @var \GambioStoreHttp
     */
    private $http;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    /**
     * @var \GambioStoreShopInformation
     */
    private $shopInformation;
    
    
    /**
     * GambioStoreAuth constructor.
     *
     * @param \GambioStoreConfiguration   $configuration
     * @param \GambioStoreHttp            $http
     * @param \GambioStoreShopInformation $shopInformation
     * @param \GambioStoreLogger          $logger
     */
    public function __construct(
        GambioStoreConfiguration $configuration,
        GambioStoreHttp $http,
        GambioStoreShopInformation $shopInformation,
        GambioStoreLogger $logger
    ) {
        $this->configuration   = $configuration;
        $this->http            = $http;
        $this->shopInformation = $shopInformation;
        $this->logger          = $logger;
    }
    
    
    /**
     * Requests new authentication information from the API
     *
     * @param array $headers
     *
     * @return bool Whether the request was successful or not
     * @throws \GambioStoreRequestingAuthInvalidStatusException
     */
    public function requestNewAuthWithHeaders(array $headers)
    {
        $apiUrl               = $this->configuration->get('GAMBIO_STORE_API_URL');
        $shopInformationArray = $this->shopInformation->getShopInformation();
        $response             = $this->http->post($apiUrl . '/request_auth',
            json_encode(['shopInformation' => $shopInformationArray]), [
                CURLOPT_HTTPHEADER => $headers
            ]);
        
        $statusCode = $response->getInformation(CURLINFO_HTTP_CODE);
        
        switch ($statusCode) {
            case 200:
                return true;
            case 401:
                return false;
            default:
                throw new GambioStoreRequestingAuthInvalidStatusException('Received invalid status code when requesting new authentication',
                    $statusCode);
        }
    }
}
