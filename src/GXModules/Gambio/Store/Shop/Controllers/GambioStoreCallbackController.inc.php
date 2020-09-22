<?php
/* --------------------------------------------------------------
   GambioStoreCallbackController.inc.php 2019-04-02
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2019 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../../GambioStoreConnector.inc.php';

/**
 * Class GambioStoreCallbackController
 *
 * Allows for Callbacks to the Shop from the Gambio Store API
 *
 * @category System
 * @package  AdminHttpViewControllers
 */
class GambioStoreCallbackController extends HttpViewController
{
    /**
     * @var \GambioStoreConnector
     */
    private $connector;
    
    /**
     * @var \GambioStoreConfiguration
     */
    private $configuration;
    
    /**
     * @var \GambioStoreAuth
     */
    private $auth;
    
    
    /**
     * Setup of our Connector classes
     */
    private function setup()
    {
        $this->connector = GambioStoreConnector::getInstance();
        $this->configuration = $this->connector->getConfiguration();
        $this->auth = $this->connector->getAuth();
    }
    
    
    /**
     * Currently not implemented
     *
     * @return \JsonHttpControllerResponse
     */
    public function actionDefault()
    {
        
        $response = [
            'success' => false,
            'notice'  => 'Method not implemented'
        ];
        
        return new JsonHttpControllerResponse($response);
    }
    
    
    /**
     * Verifies whether the Token that the Gambio Store received is the same token that is stored in this Shop
     *
     * @return \JsonHttpControllerResponse
     */
    public function actionVerify()
    {
        $this->setup();
    
        $storeToken = $this->_getPostData('storeToken');
    
        $result = $this->connector->verifyToken($storeToken);
    
        return new JsonHttpControllerResponse([
            'success' => $result
        ]);
    }
    
    
    /**
     * Receives a new auth code for the Store and requests the first access and refresh token
     *
     * @return \JsonHttpControllerResponse
     * @throws \GambioStoreRequestingAuthInvalidStatusException
     */
    public function actionIssueAuthCode()
    {
        $this->setup();
        
        $authCode = $this->_getPostData('authCode');
        
        if ($authCode === null) {
            return new JsonHttpControllerResponse([
                'success' => false
            ]);
        }
        
        $clientId = $this->configuration->get('GAMBIO_STORE_TOKEN');
        
        $result = $this->auth->requestNewAuthWithHeaders([
            'X-CLIENT-ID' => $clientId,
            'X-AUTH-CODE' => $authCode
        ]);
        
        return new JsonHttpControllerResponse([
            'success' => $result
        ]);
    }
    
    
    /**
     * Receives a new access and optionally a refresh token
     *
     * @return \JsonHttpControllerResponse
     * @throws \GambioStoreRequestingAuthInvalidStatusException
     */
    public function actionReceiveAuth()
    {
        $this->setup();
        
        $accessToken = $this->_getPostData('accessToken');
        $refreshToken = $this->_getPostData('refreshToken');
    
        if ($accessToken === null) {
            return new JsonHttpControllerResponse([
                'success' => false
            ]);
        }
        
        $this->configuration->set('GAMBIO_STORE_ACCESS_TOKEN', $accessToken);
        
        if ($refreshToken !== null) {
            $this->configuration->set('GAMBIO_STORE_REFRESH_TOKEN', $refreshToken);
        }
        
        return new JsonHttpControllerResponse([
            'success' => true
        ]);
    }
}
