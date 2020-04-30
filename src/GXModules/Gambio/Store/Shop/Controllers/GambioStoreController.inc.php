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
     * Instantiates our connector and configuration
     */
    private function setup()
    {
        $factory                   = MainFactory::create('GambioStoreConnectorFactory');
        $this->connector           = $factory->createConnector();
        $this->configuration       = $this->connector->getConfiguration();
        $this->languageTextManager = MainFactory::create('LanguageTextManager', 'gambio_store',
            $_SESSION['languages_id']);
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
            $this->configuration->set('GAMBIO_STORE_IS_REGISTERED', 'false');
            
            return new RedirectHttpControllerResponse('./admin.php?do=GambioStore');
        }
        
        $title             = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template          = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2)
                                                                     . '/Templates/gambio_store.html'));
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $assets            = $this->getIFrameAssets();
        $data              = [];
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        if ($this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') === 'false') {
            $data = $this->getIFrameTemplateData('/dataprocessing');
        } elseif ($this->configuration->get('GAMBIO_STORE_IS_REGISTERED') === 'false') {
            $data = $this->getIFrameTemplateData('/register');
        } elseif ($this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') === 'true') {
            $contentNavigation = $this->getStoreNavigation();
            $data              = $this->getIFrameTemplateData('/downloads');
        }
        
        return MainFactory::create('AdminLayoutHttpControllerResponse', $title, $template, $data, $assets,
            $contentNavigation);
    }
    
    
    /**
     * Displays the installations page on the iframe
     *
     * @return mixed|\RedirectHttpControllerResponse
     */
    public function actionInstallations()
    {
        if ($this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') !== 'true') {
            return $this->actionDefault();
        }
        
        $title    = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Templates/gambio_store.html'));
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        $data              = $this->getIFrameTemplateData('/installations');
        $assets            = $this->getIFrameAssets();
        $contentNavigation = $this->getStoreNavigation();
        
        return MainFactory::create('AdminLayoutHttpControllerResponse', $title, $template, $data, $assets,
            $contentNavigation);
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
            
            return MainFactory::create('JsonHttpControllerResponse', ['success' => true]);
        }
        
        $title    = new NonEmptyStringType($this->languageTextManager->get_text('PAGE_TITLE'));
        $template = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2)
                                                            . '/Templates/gambio_store_configuration.html'));
        
        $data = MainFactory::create('KeyValueCollection', ['url' => $gambioStoreUrl]);
        
        $assets = MainFactory::create('AssetCollection', [
            MainFactory::create('Asset', 'gambio_store.lang.inc.php')
        ]);
        
        $contentNavigation = $this->getStoreNavigation();
        
        return MainFactory::create('AdminLayoutHttpControllerResponse', $title, $template, $data, $assets,
            $contentNavigation);
    }
    
    
    /**
     * Marks the data processing terms as accepted
     *
     * @return mixed|\RedirectHttpControllerResponse
     */
    public function actionAcceptDataProcessing()
    {
        $this->configuration->set('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING', 'true');
        
        return $this->actionDefault();
    }
    
    
    /**
     * Gets the store URL
     *
     * @return string
     */
    private function getGambioStoreUrl()
    {
        $gambioUrl = (string)$this->configuration->get('GAMBIO_STORE_URL');
        
        // Fall back to the production Gambio Store URL if none is set.
        if ($gambioUrl === '') {
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
        if (!$gambioToken) {
            $tokenGenerator = MainFactory::create('GambioStoreTokenGenerator');
            $gambioToken    = $tokenGenerator->generateToken();
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
        return MainFactory::create('AssetCollection', [
            MainFactory::create('Asset', 'gambio_store.lang.inc.php'),
            MainFactory::create('Asset', 'promise_polyfill.js'),
            MainFactory::create('Asset', 'fetch_polyfill.js'),
            MainFactory::create('Asset', 'messenger.js'),
            MainFactory::create('Asset', 'translation.js'),
            MainFactory::create('Asset', 'callShop.js'),
            MainFactory::create('Asset', 'iframe.js'),
            MainFactory::create('Asset', 'package.js'),
            MainFactory::create('Asset', 'shop.js'),
            MainFactory::create('Asset', 'updateCounter.js')
        ]);
    }
    
    
    /**
     * Returns the content navigation for the store pages (once registered and accepted privacy stuff)
     *
     * @return mixed
     */
    private function getStoreNavigation()
    {
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        $contentNavigation->add(new StringType($this->languageTextManager->get_text('DOWNLOADS', 'gambio_store')),
            new StringType('admin.php?do=GambioStore'), new BoolType(true));
        $contentNavigation->add(new StringType($this->languageTextManager->get_text('INSTALLED_PACKAGES_AND_UPDATES',
            'gambio_store')), new StringType('admin.php?do=GambioStore/Installations'), new BoolType(false));
        
        return $contentNavigation;
    }
    
    
    /**
     * Returns the data for the iframe template
     *
     * @return array
     */
    private function getIFrameTemplateData($urlPostfix)
    {
        $translations = $this->languageTextManager->get_section_array('gambio_store', $_SESSION['languages_id']);
        
        return MainFactory::create('KeyValueCollection', [
            'storeUrl'      => $this->getGambioStoreUrl() . $urlPostfix,
            'storeToken'    => $this->getGambioStoreToken(),
            'storeLanguage' => $_SESSION['languages_id'],
            'translations'  => $translations
        ]);
    }
}
