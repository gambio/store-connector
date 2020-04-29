/* --------------------------------------------------------------
 archive.js 2018-11-29
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Archive Task
 *
 * This task will create a zip archive with all connector variants.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const fs = require('fs-extra');
	const del = require('del');
	const zip = require('zip-dir');
	
	return (async) => {
		del.sync('build');
		
		fs.mkdirSync('build');
		fs.copySync('archive', 'build');
        fs.mkdirSync('build/src');
        fs.copySync('src', 'build/src');

		zip('build', {saveTo: 'Gambio Store Connector 0.0.0.zip'}, function(error, buffer) {
			if (error) {
				console.error('Zip Error', error);
			}

			del.sync('build');

			async();
		});
	};
};
