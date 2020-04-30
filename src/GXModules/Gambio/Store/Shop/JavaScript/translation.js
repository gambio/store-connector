/**
 * Translation object to execute translation tasks in gambio store connector js files.
 *
 * @type {{}}
 */
const translation = {
	/**
	 * Returns the current language code.
	 *
	 * @return {string}
	 */
	getLanguageCode() {
		const div = document.getElementById('gambio-store-iframe');
		return  div.dataset.storeLanguage;
	}
}

export default translation;
