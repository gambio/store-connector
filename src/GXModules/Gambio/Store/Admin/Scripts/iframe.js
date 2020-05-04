import translation from './translation'
import messenger from './messenger'

/**
 * Builds the Iframe into the html document with store url and language code.
 *
 * @returns {Promise<void>}
 */
const build = async () => {
	const div = document.getElementById('gambio-store-iframe');
	const storeUrl = div.dataset.url;
	const iframe = document.createElement('iframe');
	const languageCode = translation.getLanguageCode();
	
	if (storeUrl.includes('?')) {
		iframe.src = `${storeUrl}&language=${languageCode}`;
	} else {
		iframe.src = `${storeUrl}?language=${languageCode}`;
	}
	
	iframe.id = 'storeIframe';
	iframe.style.width = '100%';
	iframe.style.height = '100%';
	iframe.style.border = 'none';
	iframe.style.overflow = 'hidden';
	
	parent.appendChild(iframe);
};

/**
 * Applies a darker background color to the page.
 */
const adjustBackgroundColor = () => {
	document.getElementById('main-content').style.background = '#F5F5F5';
}

/**
 * Callback for the message event.
 *
 * @param payload
 */
const onResponseIframeHeight = (payload) => {
	const iframe = document.getElementById('storeIframe');
	iframe.style.height = `${payload.height}px`;
};

/**
 * Is automatically called after dom content is loaded.
 * Listens to iframe type messages
 * Creates the iframe after dome is loaded.
 */
window.addEventListener('DOMContentLoaded', (event) => {
	adjustBackgroundColor();
	messenger.listenToMessage('response_iframe_height', onResponseIframeHeight);
	build().catch();
});