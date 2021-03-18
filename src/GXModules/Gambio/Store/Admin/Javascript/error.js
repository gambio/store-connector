/* --------------------------------------------------------------
 error.js 2020-05-27
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

window.GambioStore = Object.assign({}, {
	error:{
		/**
		 * Displays an error in the error box.
		 *
		 * @param prefix string Error category name to prepend to the message.
		 * @param message string The error's context message.
		 */
		show(prefix, message) {
			const errorContainer = document.getElementById('gambio-store-error-container');
			const errorAlert = document.createElement('div');
			errorAlert.classList.add("alert", "alert-danger");
			errorAlert.innerHTML = '<b>' + prefix + ':</b><br/>' + message;
			
			errorContainer.append(errorAlert);
		}
	}
}, window.GambioStore)
