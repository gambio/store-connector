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

require __DIR__ . '../../GambioStoreConnector.inc.php';

class GambioStoreController extends AdminHttpViewController
{
    /**
     *
     */
    private $gambioStoreConnector;
    
    /**
     *
     */
    private $gambioStoreConfiguration;
    
    
    private function getGambioStoreUrl()
    {
        $gambioUrl = (string) $this->gambioStoreConfiguration->get('GAMBIO_STORE_URL');
    
        // Fall back to the production Gambio Store URL if none is set.
        if ($gambioUrl === '') {
            $gambioUrl = 'https://store.gambio.com/a';
            $this->gambioStoreConfiguration->set('GAMBIO_STORE_URL', $gambioUrl);
        }
        
        return $gambioUrl;
    }
    
    private function getGambioStoreData(&$contentNavigation, $languageTextManager)
    {
        $gambioUrl = $this->getGambioStoreUrl();
        $gambioToken = $this->gambioStoreConfiguration->get('GAMBIO_STORE_TOKEN');
    
        if ($this->gambioStoreConfiguration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') === 'false') {
            $gambioUrl .= '/dataprocessing';
        } elseif ($this->gambioStoreConfiguration->get('GAMBIO_STORE_IS_REGISTERED') === 'false') {
            if (!$gambioToken) {
                $tokenGenerator = MainFactory::create('GambioStoreTokenGenerator');
                $gambioToken = $tokenGenerator->generateToken();
                $this->gambioStoreConfiguration->set('GAMBIO_STORE_TOKEN', $gambioToken);
            }
        
            $gambioUrl .= '/register';
        } elseif ($this->gambioStoreConfiguration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') === 'true') {
            $gambioUrl .= '/downloads';
        
            $contentNavigation->add(new StringType($languageTextManager->get_text('DOWNLOADS', 'gambio_store')),
                new StringType('admin.php?do=GambioStore'), new BoolType(true));
            $contentNavigation->add(new StringType($languageTextManager->get_text('INSTALLED_PACKAGES_AND_UPDATES',
                'gambio_store')), new StringType('admin.php?do=GambioStore/Installations'), new BoolType(false));
        }
        
        return [
            'storeUrl'   => $gambioUrl,
            'storeToken' => $gambioToken
        ];
    }
    
    public function actionDefault()
    {
        if ($this->_getQueryParameter('reset-token') || $this->_getQueryParameter('reset-token') === '') {
            $this->gambioStoreConfiguration->set('GAMBIO_STORE_TOKEN', '');
            $this->gambioStoreConfiguration->set('GAMBIO_STORE_IS_REGISTERED', 'false');
        
            return new RedirectHttpControllerResponse('./admin.php?do=GambioStore');
        }
    
        $languageTextManager = MainFactory::create('LanguageTextManager', 'gambio_store', $_SESSION['languages_id']);
        $title               = new NonEmptyStringType($languageTextManager->get_text('PAGE_TITLE'));
        $template            = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Html/gambio_store_downloads.html'));
        $contentNavigation   = MainFactory::create('ContentNavigationCollection', []);
        $assets = MainFactory::create('AssetCollection', [ MainFactory::create('Asset', 'gambio_store.lang.inc.php') ]);
        
        setcookie('auto_updater_admin_check', 'admin_logged_in', time() + 5 * 60, '/');
        
        $gambioStoreData = $this->getGambioStoreData($contentNavigation, $languageTextManager);
        $data = MainFactory::create('KeyValueCollection', $gambioStoreData);
        
        return MainFactory::create('AdminLayoutHttpControllerResponse',
            $title,
            $template,
            $data,
            $assets,
            $contentNavigation);
    }
    
    public function actionConfiguration()
    {
        $this->setup();
        
        $gambioStoreUrl = $this->gambioStoreConfiguration->get('GAMBIO_STORE_URL');
        
        if (isset($_POST['url'])
            && $_POST['url'] !== $gambioStoreUrl
            && (filter_var($_POST['url'], FILTER_VALIDATE_URL) === $_POST['url'])
        ) {
            $gambioStoreUrl = $_POST['url'];
            $this->gambioStoreConfiguration->set('GAMBIO_STORE_URL', $gambioStoreUrl);
        }
        
        $languageTextManager = MainFactory::create('LanguageTextManager', 'gambio_store', $_SESSION['languages_id']);
        $title               = new NonEmptyStringType($languageTextManager->get_text('PAGE_TITLE'));
        $template            = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Html/gambio_store_configuration.html'));

        $data                = MainFactory::create('KeyValueCollection', ['url' => $gambioStoreUrl]);
        
        $assets = MainFactory::create('AssetCollection', [
            MainFactory::create('Asset', 'gambio_store.lang.inc.php')
        ]);
        
        $contentNavigation = MainFactory::create('ContentNavigationCollection', []);
        
        $contentNavigation->add(new StringType($languageTextManager->get_text('DOWNLOADS', 'gambio_store')),
            new StringType('admin.php?do=GambioStore'), new BoolType(false));
        
        $contentNavigation->add(new StringType($languageTextManager->get_text('INSTALLED_PACKAGES_AND_UPDATES',
            'gambio_store')), new StringType('admin.php?do=GambioStore/Installations'), new BoolType(false));
        
        return MainFactory::create('AdminLayoutHttpControllerResponse', $title, $template,
            $data,
            $assets,
            $contentNavigation);
    }
    
    /**
     *
     */
    private function setup()
    {
        $this->gambioStoreConnector = GambioStoreConnector::getInstance();
        $this->gambioStoreConfiguration = $this->gambioStoreConnector->getConfiguration();
    }
}
