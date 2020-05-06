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
     * Sets up this class avoiding the constructor.
     * To be used in every action method.
     */
    private function setup()
    {
        $this->connector     = GambioStoreConnector::getInstance();
        $this->configuration = $this->connector->getConfiguration();
        $this->compatibility = $this->connector->getCompatibility();
    }
    
    
    /**
     * Collects shop information and sends them back.
     *
     * @return \JsonHttpControllerResponse
     */
    public function actionCollectShopInformation()
    {
        $this->setup();
        $factory = new ShopInformationFactory();
    
        $service    = $factory->createService();
        $serializer = $factory->createShopInformationSerializer();
    
        $shopInformation = $serializer->serialize($service->getShopInformation());
    
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
        
        try {
            $response = $this->connector->installPackage($_POST);
    
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
            $this->connector->uninstallPackage($_POST);
        } catch (\Exception $e) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
    
        return new JsonHttpControllerResponse(['success' => true]);
    }
    
    
    /**
     * Return whether the data processing has been accepted.
     *
     * @return JsonHttpControllerResponse
     */
    public function actionIsDataProcessingAccepted()
    {
        $this->setup();
        $isAccepted = $this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING');
    
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
    
        if (!$this->compatibility->has(GambioStoreCompatibility::FEATURE_THEME_CONTROL)) {
            return new JsonHttpControllerResponse(['isActive' => true]);
        }
    
        $themeName    = $_GET['themeName'];
        $themeControl = StaticGXCoreLoader::getThemeControl();
    
        foreach ($themeControl->getCurrentThemeHierarchy() as $theme) {
            if ($theme === $themeName) {
                return new JsonHttpControllerResponse([
                    'isActive' => true
                ]);
            }
        }
    
        return new JsonHttpControllerResponse([
            'isActive' => false
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
            || !isset($_POST['themeStorageName'])
            || !$this->compatibility->has(GambioStoreCompatibility::FEATURE_THEME_SERVICE)) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
    
        $themeService = StaticGXCoreLoader::getService('Theme');
        $themeName    = $_POST['themeStorageName'];
    
        try {
            $themeService->activateTheme($themeName);
        } catch (Exception $e) {
            return new JsonHttpControllerResponse(['success' => false]);
        }
    
        return new JsonHttpControllerResponse(['success' => true]);
    }
}
