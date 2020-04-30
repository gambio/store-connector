/* --------------------------------------------------------------
 doc.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Doc Task
 *
 * This task will generate the Store Connector documentation.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const environment = require('./lib/environment');
	const fsExtra = require('fs-extra');
	const fs = require('fs');
	
	return () => {
		const shopVersions = fs.readdirSync('docker').filter(dir => dir !== 'boilerplate');
		
		if(shopVersions.length === 0)
		{
			throw new Error('"docker/" directory has no version in it.')
		}
		
		let target = environment.getArgument('target') || `docker/${shopVersions[0]}/shop/src`;
		
		if (!fsExtra.existsSync(target)) {
			target = 'docker/' + target + '/shop/src';
			
			if (!fsExtra.existsSync(target)) {
				throw new Error('Target directory was not found at ' + target);
			}
		}
		
		return gulp.src('src/**/*')
			.pipe(gulp.dest(target));
	}
};
