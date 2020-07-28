<?php
/* --------------------------------------------------------------
   GambioStoreCompatibilityFacade.php 2020-04-29
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreCompatibilityFacade_included')) {
        
        define('GambioStoreCompatibilityFacade_included', true);
        require 'GambioStoreDatabaseFacade.php';
        
        /**
         * Class GambioStoreCompatibilityFacade
         *
         * This class is the facade for the GambioStoreCache class.
         * It allows to code to check for certain shop resources or features.
         *
         * The class is used for migration scripts that can be run after an installation or uninstallation of a package
         * through the Store. It is not included in our Connector Core logic and has a define constant to prevent it
         * from being loaded until desired. This was introduced to ensure that during a self update of the Connector,
         * the class will have been replaced with its updated counter part before being loaded into PHP memory,
         * allowing us to execute new code during the self update.
         *
         * Functionality is implemented by duplicating methods of the original class.
         *
         * The initial check for the StoreKey_MigrationScript constant avoids automatic class auto-loading
         * by the shop's "MainFactory" since we need a unique new version during the update.
         *
         * Example:
         *
         * $storeCompatibility->has(StoreCompatibility::RESOURCE_GM_CONFIGURATION_TABLE); // returns true or false
         */
        class GambioStoreCompatibilityFacade
        {
            /**
             * Determines whether the shop has a gm_configuration or gx_configurations table
             */
            const RESOURCE_GM_CONFIGURATION_TABLE = 'gx_configurations';
            
            /**
             * Determines whether the shop has the getThemeControl method on the StaticGXCoreLoader
             */
            const FEATURE_THEME_CONTROL = 'themeControl';
            
            /**
             * Determines whether the shop has the ThemeService class
             */
            const FEATURE_THEME_SERVICE = 'themeService';
            
            
            /**
             * GambioStoreCompatibilityFacade constructor.
             *
             * @param \GambioStoreDatabaseFacade $database
             */
            public function __construct(GambioStoreDatabaseFacade $database)
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
                switch ($resource) {
                    case self::RESOURCE_GM_CONFIGURATION_TABLE:
                        return !$this->doesGxConfigurationTableExists();
                    
                    case self::FEATURE_THEME_CONTROL:
                        return $this->doesFeatureThemeControlExists();
                    
                    case self::FEATURE_THEME_SERVICE:
                        return $this->doesFeatureThemeServiceExists();
                    
                    default:
                        return false;
                }
            }
            
            
            /**
             * Determines whether the database has the gx_configurations table
             *
             * @return bool
             */
            private function doesGxConfigurationTableExists()
            {
                $sql = 'SELECT * FROM `information_schema`.`tables` WHERE `table_schema` = :database AND `table_name` = :table_name;';
                
                $query = $this->database->query($sql,
                    [':database' => DB_DATABASE, ':table_name' => self::RESOURCE_GM_CONFIGURATION_TABLE]);
                
                return (bool)$query->rowCount();
            }
            
            
            /**
             * Determines whether the StaticGXCoreLoader class has the getThemeControl method
             *
             * @return bool
             */
            private function doesFeatureThemeControlExists()
            {
                return method_exists('StaticGXCoreLoader', 'getThemeControl');
            }
            
            
            /**
             * Determines whether the shop has the ThemeService class
             *
             * @return bool
             */
            private function doesFeatureThemeServiceExists()
            {
                return class_exists('ThemeService');
            }
        }
    }
}
