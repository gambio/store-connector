/* --------------------------------------------------------------
 shop.js 2022-02-03
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2022 Gambio GmbH
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
 * Send shop information data to Iframe
 *
 * @param {Object} shopInfo Shop information data
 */
const sendShopInfo = (shopInfo) => {
	GambioStore.messenger.send('send_shop_information', {shopInfo});
};

/**
 * Requests new auth headers from the Connector and sends them to the GUI
 */
const prepareAndSendNewAuthHeaders = async () => {
	try {
		const authHeaders = await GambioStore.callShop('admin.php?do=GambioStoreAjax/requestNewAuth')
		
		GambioStore.messenger.send('send_auth_headers', {authHeaders});
	} catch (error) {
		switch (error.type) {
			case(GambioStore.networkErrors.JSON_PARSE_ERROR):
				GambioStore.error.show(
					GambioStore.translation.translate('WARNING_TITLE'),
					GambioStore.translation.translate('NEW_AUTH_JSON_PARSE_ERROR')
				);
				break;
			case(GambioStore.networkErrors.NETWORK_ERROR):
				GambioStore.error.show(
					GambioStore.translation.translate('WARNING_TITLE'),
					GambioStore.translation.translate('NEW_AUTH_NETWORK_ERROR')
				);
				break;
			default:
				GambioStore.error.show(
					GambioStore.translation.translate('WARNING_TITLE'),
					GambioStore.translation.translate('UNKNOWN_ERROR')
				);
				break;
		}
	}
}

/**
 * Collects the
 * @returns {Promise<void>}
 */
const sendCollectedShopInformation = async () => {
	const shopInformation = await GambioStore.shop.fetchShopInfo();
	await sendShopInfo(shopInformation);
}

/**
 * Sends the auth headers from the iframe tag to the gui.
 */
const sendAuthHeaders = () => {
	const authHeadersString = document.getElementById('gambio-store-iframe').dataset.storeAuthHeaders;
	const authHeaders = JSON.parse(authHeadersString);
	GambioStore.messenger.send('send_auth_headers', {'authHeaders': authHeaders});
};

/**
 * Sends the registration headers from the iframe tag to the gui.
 */
const sendRegistrationHeaders = () => {
	const storeToken = document.getElementById('gambio-store-iframe').dataset.storeToken;
	GambioStore.messenger.send('send_registration_headers', {
		'registrationHeaders': {
			'X-STORE-TOKEN': storeToken
		}
	});
};


/**
 * Initiate messenger listen a built document.
 */
window.addEventListener('DOMContentLoaded', () => {
	GambioStore.messenger.addListener('request_auth_headers', sendAuthHeaders);
	GambioStore.messenger.addListener('auth_expired', prepareAndSendNewAuthHeaders);
	GambioStore.messenger.addListener('request_registration_headers', sendRegistrationHeaders);
	GambioStore.messenger.addListener('update_shop_information', sendCollectedShopInformation);
	GambioStore.messenger.addListener('request_shop_information', sendCollectedShopInformation);
	GambioStore.messenger.addListener('reload_page_on_inactive_session', GambioStore.shop.reloadPageOnInactiveSession);
	
	GambioStore.messenger.addListener('reload_page', () => (window.location.reload()));
	GambioStore.messenger.addListener('scroll_to_top', () => (window.scrollTo(0, 0)));
	
	GambioStore.messenger.addListener('send_data_processing_accepted', () => {
		window.location.href = 'admin.php?do=GambioStore/AcceptDataProcessing';
	});
	GambioStore.messenger.addListener('store_migrated', async () => {
		await GambioStore.callShop('admin.php?do=GambioStoreAjax/StoreMigrated');
		window.location.reload();
	});
	
});

window.GambioStore = Object.assign({}, {
	shop: {
		/**
		 * Reloads the page if the admin session is expired.
		 * Forces the user to log out.
		 * @returns {Promise<void>}
		 */
		reloadPageOnInactiveSession: async () => {
			try {
				await GambioStore.callShop('admin.php?do=GambioStoreAjax/IsSessionActive', {
					method: 'get',
					redirect: 'error'
				});
			} catch {
				location.reload();
			}
		},
		/**
		 * Makes shop requests to clear the cache of the shop in the background.
		 * @returns {Promise<void>}
		 */
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
				
				await fetch(shopUrl);
				
			} catch (error) {
				if (typeof error.NO_SUCCESS !== 'undefined') {
					throw {type: networkErrors.NO_SUCCESS, context: error};
				}
			}
		},
		/**
		 * Return shop information
		 *
		 * @returns {Promise<Object>}
		 */
		fetchShopInfo: async () => {
			try {
				return await GambioStore.callShop('admin.php?do=GambioStoreAjax/collectShopInformation')
			} catch (error) {
				switch (error.type) {
					case(GambioStore.networkErrors.JSON_PARSE_ERROR):
						GambioStore.error.show(
							GambioStore.translation.translate('WARNING_TITLE'),
							GambioStore.translation.translate('SHOP_INFORMATION_JSON_PARSE_ERROR')
						);
						break;
					case(GambioStore.networkErrors.NETWORK_ERROR):
						GambioStore.error.show(
							GambioStore.translation.translate('WARNING_TITLE'),
							GambioStore.translation.translate('SHOP_INFORMATION_NETWORK_ERROR')
						);
						break;
					default:
						GambioStore.error.show(
							GambioStore.translation.translate('WARNING_TITLE'),
							GambioStore.translation.translate('UNKNOWN_ERROR')
						);
						break;
				}
				return {};
			}
		}
	}
}, window.GambioStore);
