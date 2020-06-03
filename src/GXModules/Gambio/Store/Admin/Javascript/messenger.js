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
window.GambioStore = Object.assign({}, {
	messenger: {
		/**
		 * Sends a message to the iframe
		 * @param type
		 * @param payload
		 */
		sendMessage(type, payload) {
			const iframe = document.getElementById('storeIframe');
            if(payload === undefined) {
                payload = null;
            }
			iframe.contentWindow.postMessage({type, payload}, '*');
		},
		/**
		 * Listens to a message from the iframe
		 * @param messageType
		 * @param callback
		 */
		listenToMessage(messageType, callback) {
			const proxyCallback = ({data}) => {
				const {type, payload} = data;
				
				if (messageType === type) {
					callback(payload);
				}
			}
			
			window.addEventListener('message', proxyCallback);
		}
	}
}, window.GambioStore);
