/* --------------------------------------------------------------
 configurationPage.js 2021-11-03
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2021 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

const urls = {
	PRODUCTION: {
		GUI: 'https://store.gambio.com',
		API: 'https://api.store.gambio.com',
	},
	STAGE: {
		GUI: 'https://stage.store.gambio.com',
		API: 'https://api.stage.store.gambio.com'
	}
}

const getInputElements = () => {
	const guiUrlElement = document.getElementById('store-gui-url');
	const apiUrlElement = document.getElementById('store-api-url');
	
	return {guiUrlElement, apiUrlElement}
}

const updateProductionUrls = () => {
	const {guiUrlElement, apiUrlElement} = getInputElements();
	
	guiUrlElement.value = urls.PRODUCTION.GUI;
	apiUrlElement.value = urls.PRODUCTION.API;
}

const updateStageUrls = () => {
	const {guiUrlElement, apiUrlElement} = getInputElements();
	
	guiUrlElement.value = urls.STAGE.GUI;
	apiUrlElement.value = urls.STAGE.API;
}

window.addEventListener('DOMContentLoaded', () => {
	const setToProductionButtonElement = document.getElementById('set-to-production-button');
	const setToStageButtonElement = document.getElementById('set-to-stage-button');
	
	setToProductionButtonElement.addEventListener('click', updateProductionUrls);
	setToStageButtonElement.addEventListener('click', updateStageUrls);
});
