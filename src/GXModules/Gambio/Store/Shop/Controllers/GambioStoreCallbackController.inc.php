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
     * Setup of our Connector classes
     */
    private function setup()
    {
        $this->connector = GambioStoreConnector::getInstance();
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
        
        return MainFactory::create("JsonHttpControllerResponse", $response);
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
    
        $result = $this->connector->verifyRegistration($storeToken);
    
        return MainFactory::create('JsonHttpControllerResponse', [
            'success' => $result
        ]);
    }
}
