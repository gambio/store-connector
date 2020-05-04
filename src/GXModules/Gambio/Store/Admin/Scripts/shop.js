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
 * This controller controls the shop page.
 *
 * @module Controllers/gambio_store
 */

/**
 * Return shop information
 *
 * @returns {Promise<Object>}
 */
const fetchShopInfo = () => {
    return fetch('admin.php?do=GambioStoreAjax/collectShopInformation');
};

/**
 * Send shop information data to Iframe
 *
 * @param {Object} shopInfo Shop information data
 */
const sendShopInfo = (shopInfo) => {
    messenger.sendMessage('send_shop_information', {shopInfo})
};

/**
 * Initiate messenger listeners upon a built document.
 */
window.addEventListener('DOMContentLoaded', () => {
    messenger.listenToMessage('update_shop_information', function() {
        fetchShopInfo().then(sendShopInfo);
    });
    messenger.listenToMessage('request_shop_information', function() {
        fetchShopInfo().then(sendShopInfo);
    });
    
    messenger.listenToMessage('request_store_token', function() {
        const storeToken = document.getElementById('gambio-store-iframe').dataset.storeToken;
        messenger.sendMessage('send_store_token', {'storeToken': storeToken});
    });
    
    messenger.listenToMessage('send_data_processing_accepted', function() {
        window.location.href = 'admin.php?do=GambioStore/AcceptDataProcessing';
    });
    
    messenger.listenToMessage('scroll_to_top', function() {
	    window.scrollTo({
		    top: 0,
		    left: 0
	    });
    });
});
