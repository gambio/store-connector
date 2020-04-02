/* --------------------------------------------------------------
 gulp_clean.js 2018-11-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
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
	const environment = require('./lib/environment');
	
	return () => {
		const variant = environment.getArgument('variant') || 'src';
		
		del.sync([
			variant + '/GXModules/*/*/Build'
		]);
	};
};
