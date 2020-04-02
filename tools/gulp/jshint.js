/* --------------------------------------------------------------
 gulp_jshint.js 2018-11-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp JSHint Task
 *
 * Perform JSHint checks in the javascript files.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = function(gulp, $) {
	return () => {
		const environment = require('./lib/environment');
		const variant = environment.getArgument('variant') || 'src';
		
		const config = {
			jquery: true,
			browser: true,
			esversion: 6,
			camelcase: false,
			eqeqeq: true,
			indent: 4,
			latedef: true,
			maxlen: 120,
			newcap: true,
			quotmark: 'single',
			strict: true,
			undef: true,
			unused: false,
			predef: [
				'jse',
				'gx',
				'js_options',
				'CKEDITOR',
				'Mustache',
				'Morris',
				'alert',
				'console',
				'moment'
			],
			eqnull: true,
			laxbreak: true,
			laxcomma: true
		};
		
		return gulp.src([
			variant + '/GXModules/**/*.js',
			'!' + variant + '/GXModules/*/*/Build/**/*.js',
		])
			.pipe($.jshint(config))
			.pipe($.jshint.reporter('jshint-stylish'));
	};
};
