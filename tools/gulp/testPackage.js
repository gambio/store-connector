/* --------------------------------------------------------------
 testPackage.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Docker Task
 *
 * @param {Gulp} gulp Gulp instance.
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const fs = require('fs-extra');
	const path = require('path');
	const zip = require('zip-dir');
	const del = require('del');
	const execSync = require('child_process').execSync;
	const environment = require('./lib/environment');
	
	const zipVersions = function(from, saveTo) {
		return new Promise(resolve => {
			zip(from, {saveTo: saveTo}, function(error, buffer) {
				if (error) {
					console.error('Zip Error', error);
				}
				resolve();
			});
		})
		
	};
	
	return (done) => {
		let registry = environment.getArgument('registry');
		
		if (!registry) {
			throw  new Error('his gulp task requires a registry path (example: /var/www/html/store-package-registry) ')
		}
		
		registry += '/storage/packages/gambio/store-connector';
		
		const basePath = path.resolve(__dirname, '../../src');
		const storePackages = 'tools/storePackages';
		const firstStorePackage = storePackages + '/v1.0.0';
		const secondStorePackage = storePackages + '/v1.0.2';
		
		del.sync(storePackages);
		
		fs.mkdirSync(storePackages);
		fs.mkdirSync(firstStorePackage);
		fs.mkdirSync(secondStorePackage);
		
		fs.copySync(basePath, firstStorePackage);
		
		fs.copySync(basePath, secondStorePackage);
		
		fs.removeSync(secondStorePackage + '/GXModules/Gambio/Store/Core/GambioStoreUpdater.php');
		fs.removeSync(secondStorePackage + '/GXModules/Gambio/Store/Core/Facades/GambioStoreFileSystemFacade.php');
		
		fs.copySync('tools/boilerplate/GambioStoreFileSystemFacade.php', secondStorePackage
			+ '/GXModules/Gambio/Store/Core/GambioStoreUpdater.php')
		fs.copySync('tools/boilerplate/GambioStoreFileSystemFacade.php', secondStorePackage
			+ '/GXModules/Gambio/Store/Core/Facades/GambioStoreFileSystemFacade.php')
		
		execSync('chmod 777 -R .', {cwd: storePackages})
		
		const fistZip = zipVersions(firstStorePackage, storePackages + '/v.1.0.0.zip')
		const secondZip = zipVersions(secondStorePackage, storePackages + '/v.1.0.2.zip')
		
		del.sync(registry, {force: true});
		Promise.all([fistZip, secondZip]).then(() => {
			fs.mkdirSync(registry);
			fs.copySync(storePackages, registry);
			execSync('chmod 777 -R .', {cwd: registry});
			del.sync(storePackages);
			done();
		});
	};
};