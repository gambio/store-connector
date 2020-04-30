/* --------------------------------------------------------------
 clean.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Clean Task
 *
 * This task will remove all the "Build" directory.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const del = require('del');
	
	return () => {
		del.sync([
			'src/GXModules/*/*/Build'
		]);
	};
};
