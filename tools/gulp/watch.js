/* --------------------------------------------------------------
 watch.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Watch Task
 *
 * This task will place a watcher upon the Store Connector scripts and styles and it will execute the
 * required operations whenever a file is changed.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = function(gulp, $) {
	return () => {
		gulp.watch([
				'src/GXModules/**/*.js',
				'!src/GXModules/**/*.min.js',
				'!src/GXModules/*/*/Build/**',
				'!src/GXModules/**/Templates/**'
			],
			['scripts']);
		
		gulp.watch([
				'src/GXModules/**/*.css',
				'!src/GXModules/**/*.min.css',
				'!src/GXModules/*/*/Build/**',
				'!src/GXModules/**/Templates/**'
			],
			['styles']);
		
		gulp.watch([
				'src/**/*',
			],
			['sync']);
	};
};
