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
     * @var \GambioStoreFileSystem
     */
    private $fileSystem;
    
    
    /**
     * GambioStoreThemes constructor.
     *
     * @param \GambioStoreCompatibility $compatibility
     * @param \GambioStoreFileSystem    $fileSystem
     */
    public function __construct(GambioStoreCompatibility $compatibility, GambioStoreFileSystem $fileSystem)
    {
        $this->compatibility = $compatibility;
        $this->fileSystem    = $fileSystem;
    }
    
    
    /**
     * Determines whether a theme is active
     *
     * @param $themeName
     *
     * @return bool
     */
    public function isActive($themeName)
    {
        if (!$this->compatibility->has(GambioStoreCompatibility::FEATURE_THEME_CONTROL)) {
            return true;
        }
        
        $themeControl = StaticGXCoreLoader::getThemeControl();
        
        foreach ($themeControl->getCurrentThemeHierarchy() as $theme) {
            if ($theme === $themeName) {
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Reimport content manager entries by theme name.
     *
     * @param $themeName
     *
     * @return void
     * @throws \UnfinishedBuildException
     */
    public function reimportContentManagerEntries($themeName)
    {
        if (!$this->isActive($themeName)) {
            return;
        }
        
        if (!$this->compatibility->has(GambioStoreCompatibility::FEATURE_THEME_SERVICE)) {
            return;
        }
        
        $themesDirectory = $this->fileSystem->getThemeDirectory();
        $themeJsonPath   = implode('/', [
            $themesDirectory,
            $themeName,
            'theme.json'
        ]);
        $themeService    = StaticGXCoreLoader::getService('Theme');
        $themeId         = ThemeId::create($themeName);
        
        if (file_exists($themeJsonPath)) {
            
            $themeJsonContent = file_get_contents($themeJsonPath);
            $themeJson        = json_decode($themeJsonContent, false);
            
            if ($themeJson !== false && isset($themeJson->contents)) {
                $themeContents = ThemeContentsParser::parse($themeJson->contents);
                $themeService->storeThemeContent($themeId, $themeContents);
            }
        }
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
        $shopRootDirectory   = new ExistingDirectory($this->fileSystem->getShopDirectory());
        $themeService        = $themeServiceFactory->createThemeService($shopRootDirectory);
        
        try {
            $themeService->activateTheme($themeName);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
