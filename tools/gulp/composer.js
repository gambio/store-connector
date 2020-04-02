/* --------------------------------------------------------------
 composer.js 2018-11-27
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */


/**
 * Gulp Composer Task
 *
 * This task will prepare the final vendor directory for the module.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const del = require('del');
	const exec = require('child_process').exec;
	const environment = require('./lib/environment');
	
	return (async) => {
		const variant = environment.getArgument('variant') || 'src';
		
		del.sync(variant + '/vendor');
		
		exec('vendor/bin/php7to5 convert vendor/gambio-hub ' + variant + '/vendor/gambio-hub', (error, stdout) => {
			if (error) {
				$.util.log($.util.colors.red(`Transpile PHP sources error: ${error}`));
				return;
			}
			
			$.util.log(`stdout: ${stdout}`);
			
			del.sync('src/vendor/gambio-hub/hubpublic/docs');
			del.sync('src/vendor/gambio-hub/hubpublic/tests');
			
			async();
		});
	};
};
