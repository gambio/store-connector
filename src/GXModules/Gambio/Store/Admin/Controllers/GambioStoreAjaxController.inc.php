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

require_once __DIR__ . '/../../GambioStoreConnector.inc.php';

use Gambio\AdminFeed\Services\ShopInformation\ShopInformationFactory;

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
     * @var \GambioStoreCompatibility
     */
    private $compatibility;
    
    /**
     * @var \GambioStoreThemes
     */
    private $themes;
    
    /**
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    
    /**
     * Sets up this class avoiding the constructor.
     * To be used in every action method.
     */
    private function setup()
    {
        $this->connector     = GambioStoreConnector::getInstance();
        $this->configuration = $this->connector->getConfiguration();
        $this->compatibility = $this->connector->getCompatibility();
        $this->themes        = $this->connector->getThemes();
        $this->fileSystem    = $this->connector->getFileSystem();
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
        
        try {
            if (isset($_POST['folderNameInsideShop'])) {
                $fileList = $this->fileSystem->getContentsRecursively($_POST['folderNameInsideShop']);
            } else {
                $fileList = $_POST['fileList'];
            }
            
            $response = $this->connector->uninstallPackage($fileList);
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
        
        if (!isset($_GET) || !isset($_GET['themeName'])) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
    
        $themeName    = $_GET['themeName'];
    
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
        
        if (!isset($_POST)
            || !isset($_POST['themeStorageName'])) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
        
        $themeName = $_POST['themeStorageName'];
        $result    = $this->themes->activateTheme($themeName);
        
        return new JsonHttpControllerResponse(['success' => $result]);
    }
}
