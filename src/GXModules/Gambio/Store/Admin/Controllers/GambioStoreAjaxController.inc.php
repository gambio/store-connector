<?php
/* --------------------------------------------------------------
   GambioStoreAjaxController.inc.php 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ .'/AbstractGambioStoreController.inc.php';
require_once __DIR__ . '/../../GambioStoreConnector.inc.php';

/**
 * Class GambioStoreAjaxController
 *
 * Allows for requests from the Browser to the Shop.
 *
 * @category System
 * @package  AdminHttpViewControllers
 */
class GambioStoreAjaxController extends AbstractGambioStoreController
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
     * @var \GambioStoreThemes
     */
    private $themes;
    
    /**
     * @var \GambioStoreLogger
     */
    private $logger;
    
    /**
     * @var \GambioStoreCompatibility
     */
    private $compatibility;
    
    
    /**
     * Sets up this class avoiding the constructor.
     * To be used in every action method.
     */
    private function setup()
    {
        $this->connector     = GambioStoreConnector::getInstance();
        $this->configuration = $this->connector->getConfiguration();
        $this->themes        = $this->connector->getThemes();
        $this->logger        = $this->connector->getLogger();
        $this->compatibility = $this->connector->getCompatibility();
    }
    
    public function actionRequestNewAuth()
    {
        $this->setup();
       
        $storeApiAuthUrl = self::getGambioStoreApiUrl($this->configuration) . '/request_auth';
        $curlHandle = curl_init();
       
        $shopInformationJson = json_encode(['shopInformation' => $this->connector->getShopInformation()]);
        $headers = ['Content-Type: application/json'];
        $refreshToken = $this->configuration->get('GAMBIO_STORE_REFRESH_TOKEN');
        if ($refreshToken) {
            $headers[] = 'X-REFRESH-TOKEN: ' . $refreshToken;
        }
        
        curl_setopt($curlHandle, CURLOPT_URL, $storeApiAuthUrl);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $shopInformationJson);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_exec($curlHandle);
        $response = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        curl_close($curlHandle);
      
        if ($response !== 200) {
            $headers = ['Content-Type: application/json',
                        'X-CLIENT-ID: ' . self::getGambioStoreToken($this->configuration, $this->connector)];
            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $storeApiAuthUrl);
            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $shopInformationJson);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
            curl_exec($curlHandle);
            $response = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            curl_close($curlHandle);
            
            if ($response !== 200) {
                return new JsonHttpControllerResponse(['success' => false, 'status' => $response]);
            }
        }
        
        return new JsonHttpControllerResponse([
            'success'=>true,
            'headers'=>self::getGambioStoreAuthHeaders($this->configuration),
            'status'=>$response
        ]);
    }
    
    /**
     * Collects shop information and sends them back.
     *
     * @return \JsonHttpControllerResponse
     */
    public function actionCollectShopInformation()
    {
        $this->setup();
        
        $shopInformation = $this->connector->getShopInformation();
        
        return new JsonHttpControllerResponse($shopInformation);
    }
    
    
    /**
     * Starts an installation or gets the progress of one
     *
     * @return mixed
     */
    public function actionInstallPackage()
    {
        $this->setup();
        
        $packageData = json_decode(stripcslashes($_POST['gambioStoreData']), true);
        
        try {
            $response = $this->connector->installPackage($packageData);
            
            return new JsonHttpControllerResponse($response);
        } catch (\Exception $e) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
    }
    
    
    /**
     * Uninstalls a package
     *
     * @return mixed
     */
    public function actionUninstallPackage()
    {
        $this->setup();
        
        $packageData = json_decode(stripcslashes($_POST['gambioStoreData']), true);
        $packageName = $packageData['folder_name_inside_shop'] | $packageData['filename'];
        
        if ($this->connector->isThemeActive($packageName)) {
            $this->logger->warning('The theme ' . $packageData['details']['title']['de']
                                   . 'is active and not allowed to be removed');
            
            // Theme is active and can not be uninstalled
            return new JsonHttpControllerResponse(['success' => false, 'errorCode' => 101]);
        }
        
        try {
            $response = $this->connector->uninstallPackage($packageData);
        } catch (\Exception $e) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
        
        return new JsonHttpControllerResponse($response);
    }
    
    
    /**
     * Return whether the data processing has been accepted.
     *
     * @return JsonHttpControllerResponse
     */
    public function actionIsDataProcessingAccepted()
    {
        $this->setup();
        $isAccepted = $this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING');
        
        if ($isAccepted) {
            $this->logger->info('Data processing is currently accepted');
        } else {
            $this->logger->notice('Data processing is currently not accepted');
        }
        
        return new JsonHttpControllerResponse(['accepted' => $isAccepted]);
    }
    
    
    /**
     * Return whether a provided theme name is the active theme.
     *
     * @return JsonHttpControllerResponse
     */
    public function actionIsThemeActive()
    {
        $this->setup();
        
        if (!isset($_GET, $_GET['themeName'])) {
            $this->logger->warning(
                'Can not check if theme is active because no theme name was provided',
                ['getParams' => $_GET]
            );
            
            return new JsonHttpControllerResponse(['success' => false]);
        }
        
        $themeName = $_GET['themeName'];
        
        return new JsonHttpControllerResponse([
            'isActive' => $this->connector->isThemeActive($themeName)
        ]);
    }
    
    
    /**
     * Activate a theme
     *
     * @return \JsonHttpControllerResponse
     */
    public function actionActivateTheme()
    {
        $this->setup();
        
        if (!isset($_POST, $_POST['themeStorageName'])) {
            $this->logger->warning(
                'Can not activate theme, because it was no theme storage name provided',
                ['getParams' => $_POST]
            );
            
            return new JsonHttpControllerResponse(['success' => false]);
        }
        
        $themeName = $_POST['themeStorageName'];
        $result    = $this->themes->activateTheme($themeName);
        
        return new JsonHttpControllerResponse(['success' => $result]);
    }
    
    
    /**
     * Returns session status. Success is always true as now other options are possible.
     *
     * @return \JsonHttpControllerResponse
     */
    public function actionIsSessionActive()
    {
        return new JsonHttpControllerResponse(['success' => true]);
    }
}
