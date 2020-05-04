/* --------------------------------------------------------------
   messenger.js 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
 */

/**
 * The messenger plugin
 */
export default {
	/**
	 * Sends a message to the iframe
	 * @param type
	 * @param payload
	 */
	sendMessage(type, payload) {
		const iframe = document.getElementById('storeIframe');
		iframe.contentWindow.postMessage({type, payload}, '*');
	},
	/**
	 * Listens to a message from the iframe
	 * @param type
	 * @param callback
	 */
	listenToMessage(type, callback) {
		const proxyCallback = ({data}) => {
			const {messageType, payload} = data;
			
			if (type === messageType) {
				callback(payload);
			}
		}
		
		window.addEventListener('message', proxyCallback);
	}
};