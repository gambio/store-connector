<?php
/* --------------------------------------------------------------
   GambioStoreUpdatesFacade.php 2020-07-10
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/
// Prevent the MainFactory from loading our files
if (defined('StoreKey_MigrationScript')) {
    if (!defined('GambioStoreUpdatesFacade_included')) {
        
        define('GambioStoreUpdatesFacade_included', true);
        require_once __DIR__ . '/../../GambioStoreConnector.inc.php';
        
        require_once __DIR__ . '/../Exceptions/GambioStoreUpdatesNotInstalledException.inc.php';
        
        /**
         * Class GambioStoreUpdatesFacade
         *
         * This class allows clients to communicate with the api.
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
         */
        class GambioStoreUpdatesFacade
        {
            /**
             * Retrieves the number of available updates for the current shop version from the store.
             * Note that this returns an empty array silently if either:
             *  - curl is missing
             *  - not registered to the store
             *  - data processing not accepted
             *
             * @return array
             * @throws \GambioStoreUpdatesNotRetrievableException
             */
            public static function fetchAvailableUpdates()
            {
                return GambioStoreConnector::getInstance()->fetchAvailableUpdates();
            }
            
            
            /**
             * This method installs updates as queried from the store-api.
             *
             * @param array $updates The updates to install.
             *
             * @throws \GambioStoreUpdatesNotInstalledException in case of failure.
             * @see \GambioStoreUpdatesFacade::fetchAvailableUpdates()
             *
             */
            public static function installUpdates(array $updates)
            {
                try {
                    foreach ($updates as $update) {
                        GambioStoreConnector::getInstance()->installPackage($update);
                    }
                } catch (Exception $exception) {
                    $message = 'An update could not be installed!';
                    GambioStoreConnector::getInstance()->getLogger()->error($message, [
                        'error' => [
                            'code'    => $exception->getCode(),
                            'message' => $exception->getMessage(),
                            'file'    => $exception->getFile(),
                            'line'    => $exception->getLine()
                        ],
                    ]);
                    throw new GambioStoreUpdatesNotInstalledException($message, $exception->getCode(),
                        ["updates" => $updates], $exception);
                }
            }
        }
    }
}
