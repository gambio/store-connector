/* --------------------------------------------------------------
   updateCounter.js 2021-11-24
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2021 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
 */

const setUpdatesCounter = ({updatesCounter}) => {
    const className = 'gambio-store-updates-counter';
    
    let updatesCounterElement = document.getElementsByClassName(className);
    
    if (updatesCounterElement.length && updatesCounterElement.style) {
        if (updatesCounter === 0) {
            updatesCounterElement.style.display = "none";
            return;
        }
        updatesCounterElement.style.display = "inline";
    }
    
    
    if (updatesCounterElement.length) {
        updatesCounterElement.innerHTML = updatesCounter;
        return;
    }
    
    updatesCounterElement = document.createElement('span');
    updatesCounterElement.className = 'gambio-store-updates-counter';
    updatesCounterElement.innerHTML = updatesCounter;
    
    const contentNav = document.querySelector('#main-content .content-navigation');
    
    if (!contentNav) {
        return;
    }
    
    const navItem = contentNav.children[1];
    
    if (!navItem) {
        return;
    }
    
    if (navItem.classList.contains('no-link')) {
        navItem.appendChild(updatesCounterElement);
    } else {
        navItem.querySelector('a').appendChild(updatesCounterElement);
    }
}


window.addEventListener('DOMContentLoaded', function () {
    GambioStore.messenger.addListener('updates_counter', setUpdatesCounter);
});
