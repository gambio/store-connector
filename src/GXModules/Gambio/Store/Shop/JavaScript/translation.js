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
		const languageCode = div.dataset.storeLanguage;
		
		if (languageCode !== 'en' && languageCode !== 'de') {
			return 'de';
		}
		return languageCode;
	},
	/**
	 * Returns the translated phrase
	 * @param phrase
	 * @returns {*}
	 */
	translate(phrase) {
		const div = document.getElementById('gambio-store-iframe');
		const translations = div.dataset.translations;
		const languageCode = this.getLanguageCode();
		
		return translations[languageCode][phrase];
	}
}

export default translation;
