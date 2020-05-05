/**
 * A wrapper to the fetch API.
 * Serves as an HTTP Client to the parent shop.
 *
 * @param params see fetch API params.
 * @returns {Promise<unknown>}
 */
window.GambioStore = Object.assign({}, {
	async callShop(...params) {
		const response = await fetch(...params);
		const jsonResponse = await response.json();
		if (jsonResponse.success === false) {
			throw new Error();
		}
		return jsonResponse
	}
}, window.GambioStore);
