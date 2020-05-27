/**
 * Possible error types that may be occurring during a call to the parent shop.
 * 
 * @type {{NETWORK_ERROR: string, JSON_PARSE_ERROR: string, NO_SUCCESS: string}}
 */
const networkErrors = {
    JSON_PARSE_ERROR: 'jsonParseError',
    NETWORK_ERROR: 'networkError',
    NO_SUCCESS: 'noSuccess'
}

/**
 * A wrapper to the fetch API.
 * Serves as an HTTP Client to the parent shop.
 *
 * @param params see fetch API params.
 * @returns {Promise<unknown>}
 */
window.GambioStore = Object.assign({}, {
    networkErrors,
	callShop: (...params) => {
	    return new Promise((resolve,reject) => {
            fetch(...params).then(response => {
                response.json().then(jsonResponse => {
                    if (jsonResponse.success === false) {
                        reject({type: networkErrors.NO_SUCCESS, context: jsonResponse});
                    } else {
                        resolve(jsonResponse);
                    }
                }).catch(jsonParseError => {
                    reject({type: networkErrors.JSON_PARSE_ERROR, context: jsonParseError});
                });
            }).catch(networkError => {
                reject({type: networkErrors.NO_SUCCESS, contex: networkError});
            });
        })
	}
}, window.GambioStore);
