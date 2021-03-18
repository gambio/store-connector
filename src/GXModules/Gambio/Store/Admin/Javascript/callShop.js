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
 * @returns {Promise<{}>}
 */
window.GambioStore = Object.assign({}, {
	networkErrors,
	callShop: async (...params) => {
		let response, jsonResponse
		
		try {
			response = await fetch(...params);
		} catch (networkError) {
			throw {type: networkErrors.NO_SUCCESS, context: networkError}
		}
		
		try {
			jsonResponse = await response.json();
		} catch (jsonParseError) {
			throw {type: networkErrors.JSON_PARSE_ERROR, context: jsonParseError}
		}
		
		
		if (jsonResponse.success === false) {
			throw {type: networkErrors.NO_SUCCESS, context: jsonResponse};
		}
		
		return jsonResponse;
	},
	visitShop: async (...params) => {
		try {
			return await fetch(...params);
		} catch (networkError) {
			throw {type: networkErrors.NO_SUCCESS, context: networkError};
		}
	}
}, window.GambioStore);
