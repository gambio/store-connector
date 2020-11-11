/* --------------------------------------------------------------
 shop.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

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
    return new Promise((resolve) => {
        GambioStore.callShop('admin.php?do=GambioStoreAjax/collectShopInformation')
            .then(resolve)
            .catch(err => {
                switch(err.type) {
                    case(GambioStore.networkErrors.JSON_PARSE_ERROR):
                        GambioStore.showError(
                            GambioStore.translation.translate('WARNING_TITLE'), 
                            GambioStore.translation.translate('SHOP_INFORMATION_JSON_PARSE_ERROR')
                        );
                        break;
                    case(GambioStore.networkErrors.NETWORK_ERROR):
                        GambioStore.showError(
                            GambioStore.translation.translate('WARNING_TITLE'),
                            GambioStore.translation.translate('SHOP_INFORMATION_NETWORK_ERROR')
                        );
                        break;
                    default:
                        GambioStore.showError(
                            GambioStore.translation.translate('WARNING_TITLE'),
                            GambioStore.translation.translate('UNKNOWN_ERROR')
                        );
                        break;
                }
                return {};
            });
    })
};

/**
 * Send shop information data to Iframe
 *
 * @param {Object} shopInfo Shop information data
 */
const sendShopInfo = (shopInfo) => {
	GambioStore.messenger.sendMessage('send_shop_information', {shopInfo});
};

/**
 * Requests new auth headers from the Connector and sends them to the GUI
 */
const prepareAndSendNewAuthHeaders = () => {
	GambioStore.callShop('admin.php?do=GambioStoreAjax/requestNewAuth')
		.then((authHeaders) => {
			GambioStore.messenger.sendMessage('send_auth_headers', {authHeaders});
		})
		.catch(err => {
			switch(err.type) {
				case(GambioStore.networkErrors.JSON_PARSE_ERROR):
					GambioStore.showError(
						GambioStore.translation.translate('WARNING_TITLE'),
						GambioStore.translation.translate('NEW_AUTH_JSON_PARSE_ERROR')
					);
					break;
				case(GambioStore.networkErrors.NETWORK_ERROR):
					GambioStore.showError(
						GambioStore.translation.translate('WARNING_TITLE'),
						GambioStore.translation.translate('NEW_AUTH_NETWORK_ERROR')
					);
					break;
				default:
					GambioStore.showError(
						GambioStore.translation.translate('WARNING_TITLE'),
						GambioStore.translation.translate('UNKNOWN_ERROR')
					);
					break;
			}
		});
}


/**
 * Initiate messenger listeners upon a built document.
 */
window.addEventListener('DOMContentLoaded', () => {
	GambioStore.messenger.listenToMessage('update_shop_information', function() {
		fetchShopInfo().then(sendShopInfo);
	});
	GambioStore.messenger.listenToMessage('request_shop_information', function() {
		fetchShopInfo().then(sendShopInfo);
	});
	
	GambioStore.messenger.listenToMessage('request_auth_headers', function() {
		const authHeaders = JSON.parse(document.getElementById('gambio-store-iframe').dataset.storeAuthHeaders);
		GambioStore.messenger.sendMessage('send_auth_headers', {'authHeaders': authHeaders});
	});
	
	GambioStore.messenger.listenToMessage('request_registration_headers', function() {
		const clientId = document.getElementById('gambio-store-iframe').dataset.storeClientId;
		GambioStore.messenger.sendMessage('send_registration_headers', {'registrationHeaders': {
			'X-CLIENT-ID': clientId
			}});
	});
	
	GambioStore.messenger.listenToMessage('send_data_processing_accepted', function() {
		window.location.href = 'admin.php?do=GambioStore/AcceptDataProcessing';
	});
	
	GambioStore.messenger.listenToMessage('reload_page', function() {
		window.location.reload(true);
	})
	
	GambioStore.messenger.listenToMessage('auth_expired', prepareAndSendNewAuthHeaders);
	
	GambioStore.messenger.listenToMessage('scroll_to_top', function() {
		window.scrollTo({
			top: 0,
			left: 0
		});
	});
});

window.GambioStore = Object.assign({}, {
    clearShopCache: async () => {
        try {
            await Promise.all([
                GambioStore.visitShop('clear_cache.php?manual_output=submit', {
                    method: 'get'
                }),
                GambioStore.visitShop('clear_cache.php?manual_text_cache=submit', {
                    method: 'get'
                }),
                GambioStore.visitShop('clear_cache.php?manual_data_cache=submit', {
                    method: 'get'
                })
            ]);
        
            const shopUrl = window.location.pathname.replace('admin/admin.php', '');
        
            await fetch(shopUrl).catch(networkError => {
                throw({type: networkErrors.NO_SUCCESS, contex: networkError});
            });
        } catch (e) {}
    }
}, window.GambioStore);
