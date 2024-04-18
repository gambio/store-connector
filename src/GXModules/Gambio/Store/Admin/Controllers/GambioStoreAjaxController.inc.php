<?php
/* --------------------------------------------------------------
   GambioStoreAjaxController.inc.php 2022-02-08
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2022 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../../GambioStoreConnector.inc.php';

/**
 * Class GambioStoreAjaxController
 *
 * Allows for requests from the Browser to the Shop.
 *
 * @category System
 * @package  AdminHttpViewControllers
 */
class GambioStoreAjaxController extends AdminHttpViewController
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
     * @var \GambioStoreAuth
     */
    private $auth;
    
    /**
     * @var \GambioStoreCache
     */
    private $cache;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    
    /**
     * Request new auth headers from api.
     *
     * @return \JsonHttpControllerResponse
     * @throws \GambioStoreHttpErrorException
     * @throws \GambioStoreHttpServerMissingException
     * @throws \GambioStoreRelativeShopPathMissingException
     * @throws \GambioStoreShopClassMissingException
     * @throws \GambioStoreShopKeyMissingException
     * @throws \GambioStoreShopVersionMissingException
     */
    public function actionRequestNewAuth()
    {
        $this->setup();
        
        try {
            $refreshToken = $this->configuration->get('GAMBIO_STORE_REFRESH_TOKEN');
            if ($refreshToken) {
                $headers = [
                    'X-REFRESH-TOKEN: ' . $refreshToken,
                    'X-STORE-TOKEN: ' . $this->getGambioStoreToken()
                ];
                if ($this->auth->requestNewAuthWithHeaders($headers)) {
                    return new JsonHttpControllerResponse([
                        'success' => true,
                        'headers' => $this->auth->getGambioStoreAuthHeaders(),
                        'status'  => 200
                    ]);
                }
            }
            
            $headers = [
                'Content-Type: application/json',
                'X-STORE-TOKEN: ' . $this->getGambioStoreToken()
            ];
            if ($this->auth->requestNewAuthWithHeaders($headers)) {
                return new JsonHttpControllerResponse([
                    'success' => true,
                    'headers' => $this->auth->getGambioStoreAuthHeaders(),
                    'status'  => 200
                ]);
            }
            
            return new JsonHttpControllerResponse(['success' => false, 'status' => 401]);
        } catch (GambioStoreRequestingAuthInvalidStatusException $exception) {
            return new JsonHttpControllerResponse(['success' => false, 'status' => $exception->getCode()]);
        }
    }
    
    
    /**
     * Sets up this class avoiding the constructor.
     * To be used in every action method.
     */
    private function setup()
    {
        $this->connector     = GambioStoreConnector::getInstance();
        $this->auth          = $this->connector->getAuth();
        $this->configuration = $this->connector->getConfiguration();
        $this->themes        = $this->connector->getThemes();
        $this->logger        = $this->connector->getLogger();
        $this->compatibility = $this->connector->getCompatibility();
        $this->cache         = $this->connector->getCache();
        $this->fileSystem    = $this->connector->getFileSystem();
    }
    
    
    /**
     * Gets the store token
     *
     * @return mixed
     * @var \GambioStoreConnector     $connector
     *
     * @var \GambioStoreConfiguration $configuration
     */
    private function getGambioStoreToken()
    {
        $gambioStoreToken = $this->configuration->get('GAMBIO_STORE_TOKEN');
        if (empty($gambioStoreToken)) {
            $gambioStoreToken = $this->connector->generateToken();
            $this->configuration->set('GAMBIO_STORE_TOKEN', $gambioStoreToken);
        }
        
        return $gambioStoreToken;
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
     * @throws GambioStoreHttpErrorException
     */
    public function actionInstallPackage()
    {
        $this->setup();
        
        $packageData = json_decode(stripcslashes($_POST['gambioStoreData']), true);
        
        $subscribed = $this->connector->getSubscriptionStatus(
            $packageData['packageId'],
            $packageData['clientId'],
        );
        
        if (!$subscribed) {
            return new JsonHttpControllerResponse(['success' => false, 'errorCode' => 402]);
        }
        
        try {
            $response = $this->connector->installPackage($packageData);
            
            return new JsonHttpControllerResponse($response);
        } catch (Exception $e) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
    }
    
    
    /**
     * Uninstalls a package
     *
     * @return JsonHttpControllerResponse
     */
    public function actionUninstallPackage()
    {
        $this->setup();
        
        $packageData = json_decode(stripcslashes($_POST['gambioStoreData']), true);
        $packageName = $packageData['folder_name_inside_shop'] | $packageData['filename'];
        
        if ($this->connector->isThemeActive($packageName)) {
            $this->logger->warning(
                'The theme ' . $packageData['details']['title']['de'] . 'is active and not allowed to be removed'
            );
            
            // Theme is active and can not be uninstalled
            return new JsonHttpControllerResponse(['success' => false, 'errorCode' => 101]);
        }
        
        try {
            $response = $this->connector->uninstallPackage($packageData);
        } catch (Exception $exception) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
        
        return new JsonHttpControllerResponse($response);
    }
    
    
    /**
     * Checks if the updater needs to be run.
     *
     * If this is the case, the flag in on the cache table will be removed and the update_needed.flag will be placed
     * in the cache directory.
     *
     * @throws \GambioStoreCacheException
     */
    public function actionIsTheUpdaterNeeded()
    {
        $this->setup();
        
        $updateNeededKey = 'UPDATE_NEEDED';
        
        if (!$this->cache->has($updateNeededKey)) {
            return new JsonHttpControllerResponse(['isNeeded' => false]);
        }
        
        $isUpdateNeeded = $this->cache->get($updateNeededKey) === true;
        
        if ($isUpdateNeeded) {
            $this->placeUpdateNeededFlagInCacheDirectoryAndRemoveTheFlagOnTheCacheTable();
        }
        
        return new JsonHttpControllerResponse(['isNeeded' => $isUpdateNeeded]);
    }
    
    
    /**
     * Places the update needed flag in the cache directory and removes the entry from the cache table.
     *
     * @throws \GambioStoreCacheException
     */
    private function placeUpdateNeededFlagInCacheDirectoryAndRemoveTheFlagOnTheCacheTable()
    {
        file_put_contents($this->fileSystem->getShopDirectory() . "/cache/update_needed.flag", "");
        $this->cache->delete('UPDATE_NEEDED');
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
            $this->logger->warning('Can not check if theme is active because no theme name was provided',
                ['getParams' => $_GET]);
            
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
            $this->logger->warning('Can not activate theme, because it was no theme storage name provided',
                ['getParams' => $_POST]);
            
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
    
    
    /**
     * Sets a value that Gambio Store has been migrated.
     *
     * @return JsonHttpControllerResponse
     */
    public function actionStoreMigrated()
    {
        $this->setup();
        
        if ($this->configuration->has('GAMBIO_STORE_MIGRATED')) {
            $this->configuration->set('GAMBIO_STORE_MIGRATED', 1);
        } else {
            $this->configuration->create('GAMBIO_STORE_MIGRATED', 1);
        }
        
        return new JsonHttpControllerResponse(['success' => true]);
    }
}
