import translation from './translation'

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
 * Callback for the message event.
 *
 * @param data
 */
const onMessage = ({data}) => {
	const {type, payload} = data;
	
	if (type === 'response_iframe_height') {
		iframe.style.height = `${payload.height}px`;
	}
};

/**
 * Is automatically called after dom content is loaded.
 * Listens to iframe type messages
 * Creates the iframe after dome is loaded.
 */
window.addEventListener('DOMContentLoaded', (event) => {
	window.addEventListener('message', onMessage);
	build().catch();
});