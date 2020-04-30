/**
 * A wrapper to the fetch API. 
 * Serves as an HTTP Client to the parent shop.
 * 
 * @param params see fetch API params.
 * @returns {Promise<unknown>}
 */
export default (...params) => {
    return new Promise((resolve, reject) => {
        fetch(...params).then(r => r.json())
            .then(response => {
                if (response.success === false) {
                    reject(response);
                }
                resolve(response);
            }).catch(reject);
    })
}
