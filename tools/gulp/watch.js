/* --------------------------------------------------------------
 gulp_watch.js 2018-11-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Watch Task
 *
 * This task will place a watcher upon the Hub Connector scripts and styles and it will execute the
 * required operations whenever a file is changed.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = function(gulp, $) {
	const environment = require('./lib/environment');
	
	return () => {
		const variant = environment.getArgument('variant') || 'src';
		
		gulp.watch([
				variant + '/GXModules/**/*.js',
				'!' + variant + '/GXModules/**/*.min.js',
				'!' + variant + '/GXModules/*/*/Build/**',
				'!' + variant + '/GXModules/**/Templates/**'
			],
			['scripts']);
		
		gulp.watch([
				variant + 'src/GXModules/**/*.css',
				'!' + variant + '/GXModules/**/*.min.css',
				'!' + variant + '/GXModules/*/*/Build/**',
				'!' + variant + '/GXModules/**/Templates/**'
			],
			['styles']);
		
		gulp.watch([
				variant + '/**/*',
			],
			['sync']);
	};
};
