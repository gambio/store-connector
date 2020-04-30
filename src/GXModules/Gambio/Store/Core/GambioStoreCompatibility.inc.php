<?php
/* --------------------------------------------------------------
   GambioStoreCompatibility.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require __DIR__ . '/GambioStoreDatabase.inc.php';

/**
 * Class StoreCompatibility
 *
 * This class allows to code to check for certain shop resources or features.
 *
 * Example:
 *
 * $storeCompatibility->has(StoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE); // returns true or false
 */
class GambioStoreCompatibility
{
    /**
     *
     */
    const RESOURCE_GM_CONFIGURATION_TABLE = 'gm_configuration';
    
    /**
     *
     */
    const FEATURE_THEME_CONTROL = 'themeControl';
    
    /**
     *
     */
    const FEATURE_THEME_SERVICE = 'themeService';
    
    
    /**
     * GambioStoreCompatibility constructor.
     *
     * @param \GambioStoreDatabase $database
     */
    public function __construct(GambioStoreDatabase $database)
    {
        $this->database = $database;
    }
    
    
    /**
     * Checks if a given feature is available
     *
     * @param $resource
     *
     * @return bool
     */
    public function has($resource)
    {
        switch($resource) {
            case self::RESOURCE_GM_CONFIGURATION_TABLE:
                return $this->doesGxConfigurationTableExists();
    
            case self::FEATURE_THEME_CONTROL:
                return $this->doesFeatureThemeControlExists();
    
            case self::FEATURE_THEME_SERVICE:
                return $this->doesFeatureThemeServiceExists();
                
            default: return false;
        }
    }
    
    private function doesGxConfigurationTableExists()
    {
        $sql = 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :database AND table_name = :table_name;';

        $query = $this->database->query($sql, [':database' => DB_DATABASE, ':table_name' => self::RESOURCE_GM_CONFIGURATION_TABLE]);
        
        return (bool) $query->rowCount();
    }
    
    private function doesFeatureThemeControlExists()
    {
        return method_exists('StaticGXCoreLoader','getThemeControl');
    }
    
    private function doesFeatureThemeServiceExists()
    {
        return class_exists('ThemeService');
    }
}

