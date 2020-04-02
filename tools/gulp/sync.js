/* --------------------------------------------------------------
 gulp_doc.js 2018-11-02
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
 * This task will generate the Hub Connector documentation.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const environment = require('./lib/environment');
	const fs = require('fs-extra');

	return () => {
		const variant = environment.getArgument('variant') || 'src';
		let target = environment.getArgument('target') || 'docker/4.1_develop/shop/src';

		if (!fs.existsSync(target)) {
			target = 'docker/' + target + '/shop/src';

			if (!fs.existsSync(target)) {
				throw new Error('Target directory was not found at ' + target);
			}
		}

		return gulp.src(variant + '/**/*')
			.pipe(gulp.dest(target));
	}
};
