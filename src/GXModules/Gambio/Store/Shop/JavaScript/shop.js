/* --------------------------------------------------------------
 shop.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

import messenger from './messenger';

/**
 * ## Shop
 *
 * This controller will handles the frontend logic of the Gambio Store page.
 *
 * @module Controllers/gambio_store
 */
export default function({storeToken}) {
    return {
        /**
         * Return shop information
         *
         * @returns {Promise<Object>}
         */
        fetchShopInfo: function() {
            return fetch('admin.php?do=GambioStoreAjax/collectShopInformation');
        },
        
        /**
         * Return whether the data processing has been accepted.
         *
         * @returns {Promise<Object>}
         */
        isDataProcessingAccepted: function() {
            return fetch('admin.php?do=GambioStoreAjax/isDataProcessingAccepted');
        },
        
        /**
         * Attach message event listener
         */
        listenToMessages: function() {            
            messenger.listenToMessage('update_shop_information', function() {
                fetchShopInfo().then(sendShopInfo);
            });
            messenger.listenToMessage('request_shop_information', function() {
                fetchShopInfo().then(sendShopInfo);
            });
            
            messenger.listenToMessage('request_store_token', function() {
                messenger.sendMessage('send_store_token', {'storeToken': storeToken});
            });
            
            messenger.listenToMessage('send_data_processing_accepted', function() {
                window.location.href = 'admin.php?do=GambioStore/AcceptDataProcessing';
            });
            
            messenger.listenToMessage('reload_page', function() {
                window.location.reload(true);
            });
        },
        
        /**
         * Send shop information data to Iframe
         *
         * @param {Object} shopInfo Shop information data
         */
        sendShopInfo: function(shopInfo) {
            messenger.sendMessage('send_shop_information', {shopInfo})
        }
    }
}
