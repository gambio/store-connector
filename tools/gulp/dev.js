/* --------------------------------------------------------------
 dev.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Dev Task
 *
 * This task will initialize the required sub-tasks for development.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const gulpsync = require('gulp-sync')(gulp);
	const del = require('del');
	const fs = require('fs');
	const environment = require('./lib/environment');
	
	return () => {
		global.devEnvironment = true;
		const shopVersions = fs.readdirSync('docker').filter(dir => dir !== 'boilerplate');
		
		if(shopVersions.length === 0)
		{
			throw new Error('"docker/" directory has no version in it.')
		}
		
		let target = environment.getArgument('target') || `docker/${shopVersions[0]}/shop/src`;
		
		del.sync([
			target + '/GXModules/Gambio/Hub'
		]);
		
		gulp.start(gulpsync.sync([
			'clean', 'scripts', 'styles', 'watch', 'sync'
		], 'dev'));
	};
};
