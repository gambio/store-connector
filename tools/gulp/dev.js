/* --------------------------------------------------------------
 gulp_dev.js 2018-11-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
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
	const environment = require('./lib/environment');

	return () => {
		global.devEnvironment = true;

		let target = environment.getArgument('target') || 'docker/4.1_develop/shop/src';

		del.sync([
			target + '/GXModules/Gambio/Hub'
		]);

		gulp.start(gulpsync.sync([
			'clean', 'scripts', 'styles', 'watch', 'sync'
		], 'dev'));
	};
};
