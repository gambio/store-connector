<?php
/* --------------------------------------------------------------
   GambioStoreController.inc.php 2021-11-16
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2021 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../../GambioStoreConnector.inc.php';

class GambioStoreController extends AdminHttpViewController
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
     * @var LanguageTextManager
     */
    private $languageTextManager;
    
    /**
     * Error array with language keys to show error messages if necessary.
     *
     * @var array
     */
    private $errors = [];
    
    
    /**
     * Displays the installations page on the iframe
     *
     * @return mixed|\RedirectHttpControllerResponse
     */
    public function actionInstallations()
    {
        $this->setup();
        
        if ($this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') !== true) {
            $this->actionDefault();
        }
        
        if ((bool)$this->configuration->get('GAMBIO_STORE_MIGRATED') !== true) {
            $this->actionDefault();
        }
        
        if (!$this->acceptableErrorsTestPassed()) {
            return $this->showCriticalErrorPage();
        }
        
        $title    = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template = new ExistingFile(new NonEmptyStringType(__DIR__ . '/../Html/gambio_store.html'));
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        $data              = $this->getIFrameTemplateData('/installations');
        $assets            = $this->getIFrameAssets();
        $contentNavigation = $this->getStoreNavigation(false, true);
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
    }
    
    
    /**
     * Instantiates our connector and configuration
     */
    private function setup()
    {
        $this->connector           = GambioStoreConnector::getInstance();
        $this->configuration       = $this->connector->getConfiguration();
        $this->languageTextManager = new LanguageTextManager('gambio_store', $_SESSION['languages_id']);
    }
    
    
    /**
     * Determines whether to display the data processing terms, the registration or the downloads page of the iframe
     *
     * @return mixed
     */
    public function actionDefault()
    {
        $this->setup();
        
        if ($this->_getQueryParameter('reset-token') || $this->_getQueryParameter('reset-token') === '') {
            $this->configuration->set('GAMBIO_STORE_TOKEN', '');
            $this->configuration->set('GAMBIO_STORE_IS_REGISTERED', false);
            
            return new RedirectHttpControllerResponse('./admin.php?do=GambioStore');
        }
        
        $title             = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template          = new ExistingFile(new NonEmptyStringType(__DIR__ . '/../Html/gambio_store.html'));
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $assets            = $this->getIFrameAssets();
        $data              = [];
        
        if (!$this->acceptableErrorsTestPassed()) {
            return $this->showCriticalErrorPage();
        }
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        if ($this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') === false) {
            $data = $this->getIFrameTemplateData('/dataprocessing');
        } elseif ($this->configuration->get('GAMBIO_STORE_IS_REGISTERED') === false) {
            $data = $this->getIFrameTemplateData('/register');
        } elseif ((bool)$this->configuration->get('GAMBIO_STORE_MIGRATED') !== true) {
            $data = $this->getIFrameTemplateData('/migrate');
        } elseif ($this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') === true) {
            $contentNavigation = $this->getStoreNavigation();
            $data              = $this->getIFrameTemplateData('/downloads');
        }
        
        if (empty($data)) {
            $this->appendError('DATABASE_INTEGRITY_ERROR');
            
            return $this->showCriticalErrorPage();
        }
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
    }
    
    
    /**
     * Checks for general errors with the Store and displays them in the frontend
     */
    private function acceptableErrorsTestPassed()
    {
        $passed = true;
        
        if (!$this->connector->getLogger()->isWritable()) {
            $this->appendError('LOGS_FOLDER_PERMISSION_ERROR');
        }
        
        if (!is_writable($this->connector->getFileSystem()->getShopDirectory() . '/GXModules/Gambio/Store')) {
            $this->appendError('STORE_FOLDER_PERMISSION_ERROR');
        }
        
        if (!extension_loaded('curl')) {
            $this->appendError('CURL_EXTENSION_MISSING');
        }
        
        if (!extension_loaded('PDO')) {
            $this->appendError('PDO_EXTENSION_MISSING');
            $passed = false;
        }
        
        return $passed;
    }
    
    
    /**
     * To be returned upon encountering a critical error.
     * Make sure to append to the errors array first.
     *
     * @return \AdminLayoutHttpControllerResponse
     */
    private function showCriticalErrorPage()
    {
        $template          = new ExistingFile(
            new NonEmptyStringType(
                __DIR__ . '/../Html/gambio_store_critical_errors.html'
            )
        );
        $assets            = $this->getIFrameAssets();
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $title             = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        
        // Fallback to 'en' in case we are not able to fetch from the session value.
        try {
            $language = $this->connector->getCurrentShopLanguageCode();
        } catch (GambioStoreLanguageNotResolvableException $e) {
            $language = 'en';
        }
        
        $data = new KeyValueCollection([
            'storeLanguage' => $language,
            'translations'  => $this->languageTextManager->get_section_array('gambio_store', $_SESSION['languages_id']),
            'errors'        => $this->errors
        ]);
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
    }
    
    
    /**
     * Returns the data for the iframe template
     *
     * @param $urlPostfix
     *
     * @return \KeyValueCollection
     */
    private function getIFrameTemplateData($urlPostfix)
    {
        $translations = $this->languageTextManager->get_section_array('gambio_store', $_SESSION['languages_id']);
        
        try {
            $language = $this->connector->getCurrentShopLanguageCode();
        } catch (GambioStoreLanguageNotResolvableException $e) {
            $language = 'en';
        }
        
        return new KeyValueCollection([
            'storeUrl'      => $this->getGambioStoreUrl() . $urlPostfix,
            'storeToken'    => $this->getGambioStoreToken(),
            'authHeaders'   => $this->getGambioStoreAuthHeaders(),
            'storeLanguage' => $language,
            'translations'  => $translations,
            'errors'        => $this->errors
        ]);
    }
    
    
    /**
     * Returns all assets that are supposed to be on the iframe page
     *
     * @return mixed
     */
    private function getIFrameAssets()
    {
        return new AssetCollection([
            new Asset('gambio_store.lang.inc.php'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/promise_polyfill.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/fetch_polyfill.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/messenger.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/translation.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/callShop.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/error.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/iframe.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/package.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/shop.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/updateCounter.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Styles/gambio_store.css')
        ]);
    }
    
    
    /**
     * Returns the content navigation for the store pages (once registered and accepted privacy stuff)
     *
     * @param bool $mainPage
     * @param bool $installationPage
     *
     * @return mixed
     */
    private function getStoreNavigation($mainPage = true, $installationPage = false, $configurationPage = false)
    {
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $contentNavigation->add(
            new StringType($this->languageTextManager->get_text('DOWNLOADS', 'gambio_store')),
            new StringType('admin.php?do=GambioStore'),
            new BoolType($mainPage)
        );
        $contentNavigation->add(
            new StringType(
                $this->languageTextManager->get_text(
                    'INSTALLED_PACKAGES_AND_UPDATES',
                    'gambio_store'
                )
            ),
            new StringType('admin.php?do=GambioStore/Installations'),
            new BoolType($installationPage)
        );
        
        if ($this->isDevEnvironment()) {
            $contentNavigation->add(
                new StringType(
                    $this->languageTextManager->get_text('CONFIGURATION', 'gambio_store')
                ),
                new StringType('admin.php?do=GambioStore/configuration'),
                new BoolType($configurationPage)
            );
        }
        
        return $contentNavigation;
    }
    
    
    /**
     * Determines if the dev environment in the shop is active.
     *
     * @return bool
     */
    private function isDevEnvironment()
    {
        return file_exists(dirname(__DIR__, 5) . '/.dev-environment');
    }
    
    
    /**
     * Appends an error to the errors array.
     *
     * @param $errorIdentifier string the error's translational identifier.
     */
    private function appendError($errorIdentifier)
    {
        $this->errors[] = $errorIdentifier;
    }
    
    
    /**
     * Gets the store URL
     *
     * @return string
     */
    private function getGambioStoreUrl()
    {
        $gambioUrl = $this->configuration->get('GAMBIO_STORE_URL');
        
        // Fall back to the production Gambio Store URL if none is set.
        if (empty($gambioUrl)) {
            $gambioUrl = 'https://store.gambio.com/a';
            $this->configuration->set('GAMBIO_STORE_URL', $gambioUrl);
        }
        
        return $gambioUrl;
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
     * Gets the auth headers
     *
     * @return array
     * @var \GambioStoreConfiguration $configuration
     *
     */
    private function getGambioStoreAuthHeaders()
    {
        return [
            'X-ACCESS-TOKEN' => $this->configuration->get('GAMBIO_STORE_ACCESS_TOKEN')
        ];
    }
    
    
    /**
     * Displays the configuration page to change the store URL
     *
     * @return mixed
     * @throws \GambioStoreCacheException
     */
    public function actionConfiguration()
    {
        if (!$this->isDevEnvironment()) {
            return new RedirectHttpControllerResponse('./admin.php?do=GambioStore');
        }
        
        $this->setup();
        
        $gambioStoreUrl    = $this->getGambioStoreUrl();
        $gambioStoreApiUrl = $this->getGambioStoreApiUrl();
        
        if (isset($_POST['url'])) {
            $gambioStoreUrl = $this->updateGambioStoreUrl($_POST['url'], $gambioStoreUrl);
        }
        
        if (isset($_POST['apiUrl'])) {
            $gambioStoreApiUrl = $this->updateGambioStoreApiUrl($_POST['apiUrl'], $gambioStoreApiUrl);
        }
        
        $title           = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template        = new ExistingFile(
            new NonEmptyStringType(__DIR__ . '/../Html/gambio_store_configuration.html')
        );
        $shopInformation = $this->connector->getShopInformation();
        
        $data = new KeyValueCollection([
            'url'              => $gambioStoreUrl,
            'apiUrl'           => $gambioStoreApiUrl,
            'storeToken'       => $this->getGambioStoreToken(),
            'connectorVersion' => $shopInformation['shop']['connectorVersion'],
            'storeType'        => $this->getStoreType($gambioStoreApiUrl)
        ]);
        
        $assets = new AssetCollection([
            new Asset('gambio_store.lang.inc.php'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/callShop.min.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/configurationPage.min.js'),
        ]);
        
        $contentNavigation = $this->getStoreNavigation(false, false, true);
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
    }
    
    
    /**
     * Updates the Gambio store URL.
     *
     * @param $url
     * @param $gambioStoreUrl
     *
     * @return string
     */
    private function updateGambioStoreUrl($url, $gambioStoreUrl)
    {
        if ($url === $gambioStoreUrl) {
            return $gambioStoreUrl;
        }
        
        if (filter_var($url, FILTER_VALIDATE_URL) !== $url) {
            return $gambioStoreUrl;
        }
        
        $this->configuration->set('GAMBIO_STORE_URL', $url);
        
        return $url;
    }
    
    
    /**
     * Updates the Gambio store api URL.
     *
     * @param $newStoreApiUrl
     * @param $currentStoreApiUrl
     *
     * @return string
     * @throws \GambioStoreCacheException
     */
    private function updateGambioStoreApiUrl($newStoreApiUrl, $currentStoreApiUrl)
    {
        if ($newStoreApiUrl === $currentStoreApiUrl) {
            return $currentStoreApiUrl;
        }
        
        if (filter_var($newStoreApiUrl, FILTER_VALIDATE_URL) !== $newStoreApiUrl) {
            return $currentStoreApiUrl;
        }
        
        $this->cacheGambioStoreRegistration($currentStoreApiUrl);
        $this->configuration->set('GAMBIO_STORE_API_URL', $newStoreApiUrl);
        $this->restoreGambioStoreRegistrationFromCache($newStoreApiUrl);
        
        return $newStoreApiUrl;
    }
    
    
    /**
     * Stores the store API registration on the store cache table.
     *
     * @param $storeApiUrl
     *
     * @throws \GambioStoreCacheException
     */
    private function cacheGambioStoreRegistration($storeApiUrl)
    {
        if (!$this->configuration->has('GAMBIO_STORE_REFRESH_TOKEN')) {
            return;
        }
        
        if (!$this->configuration->has('GAMBIO_STORE_TOKEN')) {
            return;
        }
        
        $storeCache      = $this->connector->getCache();
        $storeToken      = $this->configuration->get('GAMBIO_STORE_TOKEN');
        $refreshToken    = $this->configuration->get('GAMBIO_STORE_REFRESH_TOKEN');
        $accessToken     = $this->configuration->get('GAMBIO_STORE_ACCESS_TOKEN');
        $storeType       = $this->getStoreType($storeApiUrl);
        $storeTokenKey   = "GAMBIO_STORE_TOKEN-$storeType";
        $refreshTokenKey = "GAMBIO_STORE_REFRESH_TOKEN-$storeType";
    
        if (!$this->configuration->has('GAMBIO_STORE_ACCESS_TOKEN')) {
            $accessTokenKey  = "GAMBIO_STORE_ACCESS_TOKEN-$storeType";
            $storeCache->set($accessTokenKey, $accessToken);
        }
        
        $storeCache->set($storeTokenKey, $storeToken);
        $storeCache->set($refreshTokenKey, $refreshToken);
    }
    
    
    /**
     * Restores an old store registration, if it is present on the store cache table.
     *
     * @param $storeApiUrl
     *
     * @throws \GambioStoreCacheException
     */
    private function restoreGambioStoreRegistrationFromCache($storeApiUrl)
    {
        $storeCache = $this->connector->getCache();
        $storeType  = $this->getStoreType($storeApiUrl);
        
        $storeTokenCacheKey   = "GAMBIO_STORE_TOKEN-$storeType";
        $accessTokenCacheKey  = "GAMBIO_STORE_ACCESS_TOKEN-$storeType";
        $refreshTokenCacheKey = "GAMBIO_STORE_REFRESH_TOKEN-$storeType";
        
        if ($storeCache->has($storeTokenCacheKey)) {
            $storeToken = $storeCache->get($storeTokenCacheKey);
        }
        
        if ($storeCache->has($refreshTokenCacheKey)) {
            $refreshToken = $storeCache->get($refreshTokenCacheKey);
        }
        
        if ($storeCache->has($accessTokenCacheKey)) {
            $accessToken = $storeCache->get($accessTokenCacheKey);
        }
        
        $this->configuration->set('GAMBIO_STORE_TOKEN', isset($storeToken) ? $storeToken : '');
        $this->configuration->set('GAMBIO_STORE_REFRESH_TOKEN', isset($refreshToken) ? $refreshToken : '');
        $this->configuration->set('GAMBIO_STORE_ACCESS_TOKEN', isset($accessToken) ? $accessToken : '');
        $this->configuration->set('GAMBIO_STORE_IS_REGISTERED', isset($refreshToken));
    }
    
    
    /**
     * Gets the store api URL
     *
     * @return string
     * @var \GambioStoreConfiguration $configuration
     *
     */
    private function getGambioStoreApiUrl()
    {
        $gambioUrl = $this->configuration->get('GAMBIO_STORE_API_URL');
        
        // Fall back to the production Gambio Store api URL if none is set.
        if (empty($gambioUrl)) {
            $gambioUrl = 'https://store.gambio.com';
            $this->configuration->set('GAMBIO_STORE_API_URL', $gambioUrl);
        }
        
        return $gambioUrl;
    }
    
    
    /**
     * Determines whether the store is in Prod, Stage or something else.
     *
     * @param string $gambioStoreApiUrl
     *
     * @return string
     */
    private function getStoreType($gambioStoreApiUrl)
    {
        if (strpos($gambioStoreApiUrl, 'stage.store.gambio.com')) {
            return 'stage';
        }
        
        if (strpos($gambioStoreApiUrl, 'store.gambio.com')) {
            return 'production';
        }
        
        return 'other';
    }
    
    
    /**
     * Marks the data processing terms as accepted
     *
     * @return mixed|\RedirectHttpControllerResponse
     */
    public function actionAcceptDataProcessing()
    {
        $this->setup();
        
        $this->configuration->set('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING', true);
        
        return $this->actionDefault();
    }
}
