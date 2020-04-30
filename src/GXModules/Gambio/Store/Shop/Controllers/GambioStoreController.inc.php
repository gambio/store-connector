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

require __DIR__ . '../../connector.inc.php';

class GambioStoreController extends AdminHttpViewController
{
    /**
     * connector
     */
    private $connector;
    
    /**
     *
     */
    private $configuration;
    
    
    private function getGambioStoreUrl()
    {
        $gambioUrl = (string) $this->configuration->get('GAMBIO_STORE_URL');
    
        // Fall back to the production Gambio Store URL if none is set.
        if ($gambioUrl === '') {
            $gambioUrl = 'https://store.gambio.com/a';
            $this->configuration->set('GAMBIO_STORE_URL', $gambioUrl);
        }
        
        return $gambioUrl;
    }
    
    private function getGambioStoreData(&$contentNavigation, $languageTextManager)
    {
        $gambioUrl = $this->getGambioStoreUrl();
        $gambioToken = $this->configuration->get('GAMBIO_STORE_TOKEN');
    
        if ($this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') === 'false') {
            $gambioUrl .= '/dataprocessing';
        } elseif ($this->configuration->get('GAMBIO_STORE_IS_REGISTERED') === 'false') {
            if (!$gambioToken) {
                $tokenGenerator = MainFactory::create('GambioStoreTokenGenerator');
                $gambioToken = $tokenGenerator->generateToken();
                $this->configuration->set('GAMBIO_STORE_TOKEN', $gambioToken);
            }
        
            $gambioUrl .= '/register';
        } elseif ($this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING') === 'true') {
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
            $this->configuration->set('GAMBIO_STORE_TOKEN', '');
            $this->configuration->set('GAMBIO_STORE_IS_REGISTERED', 'false');
        
            return new RedirectHttpControllerResponse('./admin.php?do=GambioStore');
        }
    
        $this->setup();
        
        $languageTextManager = MainFactory::create('LanguageTextManager', 'gambio_store', $_SESSION['languages_id']);
        $title               = new NonEmptyStringType($languageTextManager->get_text('PAGE_TITLE'));
        $template            = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Templates/gambio_store.html'));
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
        
        $gambioStoreUrl = $this->configuration->get('GAMBIO_STORE_URL');
        
        if (isset($_POST['url'])
            && $_POST['url'] !== $gambioStoreUrl
            && (filter_var($_POST['url'], FILTER_VALIDATE_URL) === $_POST['url'])
        ) {
            $gambioStoreUrl = $_POST['url'];
            $this->configuration->set('GAMBIO_STORE_URL', $gambioStoreUrl);
        }
        
        $languageTextManager = MainFactory::create('LanguageTextManager', 'gambio_store', $_SESSION['languages_id']);
        $title               = new NonEmptyStringType($languageTextManager->get_text('PAGE_TITLE'));
        $template            = new ExistingFile(new NonEmptyStringType(dirname(__FILE__, 2) . '/Templates/gambio_store_configuration.html'));

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
        $factory = MainFactory::create('GambioStoreConnectorFactory');
        $this->connector = $factory->createConnector();
        $this->configuration = $this->gambioStoreConnector->getConfiguration();
    }
}
