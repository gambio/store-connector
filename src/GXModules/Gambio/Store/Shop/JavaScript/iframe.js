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
}