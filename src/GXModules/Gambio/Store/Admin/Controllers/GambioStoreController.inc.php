<?php
/* --------------------------------------------------------------
   GambioStoreController.inc.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once __DIR__ . '/../../Core/Exceptions/GambioStoreUpdateWasNotExecutedProperlyException.inc.php';
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
     * Error array with language keys to show error messages if nessary.
     *
     * @var array
     */
    private $errors = [];
    
    
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
        $template          = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Html/gambio_store.html'));
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $assets            = $this->getIFrameAssets();
        $data              = [];
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        if ($this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') === false) {
            $data = $this->getIFrameTemplateData('/dataprocessing');
        } elseif ($this->configuration->get('GAMBIO_STORE_IS_REGISTERED') === false) {
            $data = $this->getIFrameTemplateData('/register');
        } elseif ($this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') === true) {
            $contentNavigation = $this->getStoreNavigation();
            $data              = $this->getIFrameTemplateData('/downloads');
        }
        
        if (empty($data)) {
            throw new GambioStoreUpdateWasNotExecutedProperlyException('The updater was not executed properly. Important database values are missing for the Store.');
        }
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
    }
    
    
    /**
     * Displays the installations page on the iframe
     *
     * @return mixed|\RedirectHttpControllerResponse
     */
    public function actionInstallations()
    {
        $this->setup();
        
        if ($this->configuration->get('GAMBIO_STORE_ACCEPTED_DATA_PROCESSING') !== true) {
            return $this->actionDefault();
        }
        
        $title    = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Html/gambio_store.html'));
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        $data              = $this->getIFrameTemplateData('/installations');
        $assets            = $this->getIFrameAssets();
        $contentNavigation = $this->getStoreNavigation(false, true);
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
    }
    
    
    /**
     * Displays the configuration page to change the store URL
     *
     * @return mixed
     */
    public function actionConfiguration()
    {
        $this->setup();
        
        $gambioStoreUrl = $this->configuration->get('GAMBIO_STORE_URL');
        
        if (isset($_POST['url'])
            && $_POST['url'] !== $gambioStoreUrl
            && (filter_var($_POST['url'], FILTER_VALIDATE_URL) === $_POST['url'])) {
            $gambioStoreUrl = $_POST['url'];
            $this->configuration->set('GAMBIO_STORE_URL', $gambioStoreUrl);
        }
        
        $title    = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2)
                                                            . '/Html/gambio_store_configuration.html'));
        
        $data = new KeyValueCollection(['url' => $gambioStoreUrl]);
        
        $assets = new AssetCollection([
            new Asset('gambio_store.lang.inc.php')
        ]);
        
        $contentNavigation = $this->getStoreNavigation(false);
        
        return new AdminLayoutHttpControllerResponse($title, $template, $data, $assets, $contentNavigation);
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
     */
    private function getGambioStoreToken()
    {
        $gambioToken = $this->configuration->get('GAMBIO_STORE_TOKEN');
        if (empty($gambioToken)) {
            $gambioToken = $this->connector->generateToken();
            $this->configuration->set('GAMBIO_STORE_TOKEN', $gambioToken);
        }
        
        return $gambioToken;
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
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/iframe.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/package.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/shop.js'),
            new Asset('../GXModules/Gambio/Store/Build/Admin/Javascript/updateCounter.js')
        ]);
    }
    
    
    /**
     * Returns the content navigation for the store pages (once registered and accepted privacy stuff)
     *
     * @param bool $mainPage
     * @param bool $secondaryPage
     *
     * @return mixed
     */
    private function getStoreNavigation($mainPage = true, $secondaryPage = false)
    {
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $contentNavigation->add(new StringType($this->languageTextManager->get_text('DOWNLOADS', 'gambio_store')),
            new StringType('admin.php?do=GambioStore'), new BoolType($mainPage));
        $contentNavigation->add(new StringType($this->languageTextManager->get_text('INSTALLED_PACKAGES_AND_UPDATES',
            'gambio_store')), new StringType('admin.php?do=GambioStore/Installations'), new BoolType($secondaryPage));
        
        return $contentNavigation;
    }
    
    
    /**
     * Returns the data for the iframe template
     *
     * @param $urlPostfix
     *
     * @return \KeyValueCollection
     * @throws \GambioStoreLanguageNotResolvableException
     */
    private function getIFrameTemplateData($urlPostfix)
    {
        $translations = $this->languageTextManager->get_section_array('gambio_store', $_SESSION['languages_id']);
        
        return new KeyValueCollection([
            'storeUrl'      => $this->getGambioStoreUrl() . $urlPostfix,
            'storeToken'    => $this->getGambioStoreToken(),
            'storeLanguage' => $this->connector->getCurrentShopLanguageCode(),
            'translations'  => $translations,
            'errors'        => $this->errors
        ]);
    }
}
