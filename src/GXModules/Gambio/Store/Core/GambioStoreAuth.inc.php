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
     * @var \GambioStoreShopInformation
     */
    private $shopInformation;
    
    
    /**
     * GambioStoreAuth constructor.
     *
     * @param \GambioStoreConfiguration   $configuration
     * @param \GambioStoreHttp            $http
     * @param \GambioStoreShopInformation $shopInformation
     */
    public function __construct(
        GambioStoreConfiguration   $configuration,
        GambioStoreHttp            $http,
        GambioStoreShopInformation $shopInformation
    ) {
        $this->configuration   = $configuration;
        $this->http            = $http;
        $this->shopInformation = $shopInformation;
    }
    
    
    /**
     * Requests new authentication information from the API
     *
     * @param array $headers
     *
     * @return bool Whether the request was successful or not
     * @throws \GambioStoreHttpServerMissingException
     * @throws \GambioStoreRelativeShopPathMissingException
     * @throws \GambioStoreRequestingAuthInvalidStatusException
     * @throws \GambioStoreShopClassMissingException
     * @throws \GambioStoreShopKeyMissingException
     * @throws \GambioStoreShopVersionMissingException
     */
    public function requestNewAuthWithHeaders(array $headers)
    {
        $apiUrl               = $this->configuration->get('GAMBIO_STORE_API_URL');
        $shopInformationArray = $this->shopInformation->getShopInformation();
        
        $headers[] = 'Content-Type: application/json';
        
        try {
            $response = $this->http->post(
                $apiUrl . '/request_auth',
                json_encode(['shopInformation' => $shopInformationArray]),
                [
                    CURLOPT_HTTPHEADER => $headers
                ]
            );
        } catch (GambioStoreHttpErrorException $exception) {
            return false;
        }
        
        $statusCode = $response->getStatus();
        
        switch ($statusCode) {
            case 200:
                return true;
            case 401:
                return false;
            default:
                throw new GambioStoreRequestingAuthInvalidStatusException(
                    'Received invalid status code when requesting new authentication', $statusCode
                );
        }
    }
    
    
    /**
     * Gets the auth headers
     *
     * @return array
     * @var \GambioStoreConfiguration $configuration
     *
     */
    public function getGambioStoreAuthHeaders()
    {
        return [
            'X-ACCESS-TOKEN' => $this->configuration->get('GAMBIO_STORE_ACCESS_TOKEN')
        ];
    }
}
