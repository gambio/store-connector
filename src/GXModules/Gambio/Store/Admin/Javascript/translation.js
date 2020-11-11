/**
 * Translation object to execute translation tasks in gambio store connector js files.
 *
 * @type {{}}
 */
window.GambioStore = Object.assign({}, {
    translation: {
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
            const translations = JSON.parse(div.dataset.storeTranslations);
            
            return translations[phrase];
        }
    }
}, window.GambioStore);
