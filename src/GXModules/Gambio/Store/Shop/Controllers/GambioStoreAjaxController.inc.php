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

use Gambio\AdminFeed\Services\ShopInformation\ShopInformationFactory;

/**
 * Class GambioStoreAjaxController
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
     * Sets up this class avoiding the constructor.
     * To be used in every action method.
     */
    private function setup()
    {
        $this->connector = GambioStoreConnector::getInstance();
        $this->configuration = $this->connector->getConfiguration();
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
     * Return whether the data processing has been accepted.
     *
     * @return JsonHttpControllerResponse
     */
    public function actionIsDataProcessingAccepted()
    {
        $this->setup();
        $isAccepted = $this->configuration->get('ADMIN_FEED_ACCEPTED_SHOP_INFORMATION_DATA_PROCESSING');
        
        return MainFactory::create('JsonHttpControllerResponse', ['accepted' => $isAccepted]);
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
            return MainFactory::create('JsonHttpControllerResponse', ['success' => false]);
        }
        
        $themeName    = $_GET['themeName'];
        $themeControl = StaticGXCoreLoader::getThemeControl();
        
        foreach ($themeControl->getCurrentThemeHierarchy() as $theme) {
            if ($theme === $themeName) {
                return MainFactory::create('JsonHttpControllerResponse',
                    [
                        'isActive' => true
                    ]);
            }
        }
        
        return MainFactory::create('JsonHttpControllerResponse',
            [
                'isActive' => false
            ]);
    }
    
}
