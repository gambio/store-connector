import translation from './translation'

/**
 * Builds the Iframe into the html document with store url and language code.
 *
 * @return {Promise<void>}
 */
const build = async () => {
	const storeUrl = shop.getStoreUrl();
	const iframe = document.createElement('iframe');
	const languageCode = translation.getLanguageCode();
	
	if (storeUrl.includes('?')) {
		iframe.src = `&language=${languageCode}`;
	} else {
		iframe.src = `?language=${languageCode}`;
	}
	
	iframe.id = 'storeIframe';
	iframe.style.width = '100%';
	iframe.style.height = '100%';
	iframe.style.border = 'none';
	iframe.style.overflow = 'hidden';
	
	iframe.addEventListener('load', resolve);
	
	parent.appendChild(iframe);
}