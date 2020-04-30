/* --------------------------------------------------------------
 environment.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Environment
 *
 * Contains re-usable environment methods.
 *
 * @type {Object}
 */
module.exports = {
	/**
	 * Exports a function that returns the values of command line arguments.
	 *
	 * Example:
	 *   // $ node index.js --custom-arg custom-value
	 *   const environment = require('./lib/environment');
	 *   const value = environment.getArgument('custom-arg'); // Returns 'custom-value'.
	 *
	 * @param {String} name The argument name without the initial '--' characters.
	 *
	 * @return {String} Returns the value of the requested parameter.
	 */
	getArgument(name) {
		const index = process.argv.indexOf('--' + name);
		return index > -1 ? process.argv[index + 1] : undefined;
	}
};
