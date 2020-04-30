/* --------------------------------------------------------------
   updateCounter.js 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
 */

import messenger from 'messenger';

const setUpdatesCounter = ({updatesCounter}) => {
	const className = 'gambio-store-updates-counter';
	
	let updatesCounterElement = document.getElementsByClassName(className);
	
	if (updatesAvailable === 0) {
		if (updatesCounterElement.length && updatesCounterElement.style) {
			updatesCounterElement.style.display = "none";
		}
		return;
	} else {
		if (updatesCounterElement.length && updatesCounterElement.style) {
			updatesCounterElement.style.display = "inline";
		}
	}
	
	if (updatesCounterElement.length) {
		updatesCounterElement.innerHTML = updatesCounter;
		return;
	}
	
	updatesCounterElement = document.createElement('span');
	updatesCounterElement.className = 'gambio-store-updates-counter';
	updatesCounterElement.innerHTML = updatesCounter;
	
	const navItem = document.querySelector('#main-content .content-navigation .nav-item:last-child');
	
	if (!navItem) {
		return;
	}
	
	if (navItem.classList.contains('no-link')) {
		navItem.appendChild(updatesCounterElement);
	} else {
		navItem.querySelector('a').appendChild(updatesCounterElement);
	}
}


document.onload = function() {
	messenger.listenToMessage('updates_counter', setUpdatesCounter);
}