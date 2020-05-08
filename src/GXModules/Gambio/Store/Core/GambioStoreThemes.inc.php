<?php
/* --------------------------------------------------------------
   GambioStoreThemes.php 2020-05-06
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

/**
 * Class GambioStoreThemes
 *
 * Handles all actions related to Themes in the Shop
 */
class GambioStoreThemes
{
    /**
     * @var \GambioStoreCompatibility
     */
    private $compatibility;
    
    
    /**
     * GambioStoreThemes constructor.
     *
     * @param \GambioStoreCompatibility $compatibility
     */
    public function __construct(GambioStoreCompatibility $compatibility)
    {
        $this->compatibility = $compatibility;
    }
    
    
    /**
     * Activates a Theme
     *
     * @param string $themeName
     *
     * @return bool
     */
    public function activateTheme($themeName)
    {
        if (!$this->compatibility->has(GambioStoreCompatibility::FEATURE_THEME_SERVICE)) {
            return false;
        }
        
        $themeServiceFactory = new ThemeServiceFactory();
        $shopRootDirectory   = new ExistingDirectory(__DIR__ . '/../../../../');
        $themeService        = $themeServiceFactory->createThemeService($shopRootDirectory);
        
        try {
            $themeService->activateTheme($themeName);
    
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
