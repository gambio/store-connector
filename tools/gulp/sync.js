/* --------------------------------------------------------------
 doc.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
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
 * @param {Gulp} gulp Gulp instance.
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
	const environment = require('./lib/environment');
	const fs = require('fs-extra');
	
	const syncWithTarget = (target) => {
        if (!fs.existsSync(target)) {
            target = `docker/${target}/shop/src`;
            
            if (!fs.existsSync(target)) {
                throw new Error('Target directory was not found at ' + target);
            }
        }
        
        gulp.src('src/**/*')
            .pipe(gulp.dest(target));
    };
	
	const syncWithDockerShops = (dockerShops) => {
	    dockerShops.forEach((dockerShop) => syncWithTarget(`docker/${dockerShop}/shop/src`));
    }; 
	
	return (done) => {
		const dockerShops = fs.readdirSync('docker').filter((directory) => directory !== 'boilerplate'); 
		
		if (!dockerShops.length) {
			throw new Error('No local docker shops found, clone one by running "gulp docker".');
		}
		
		let target = environment.getArgument('target');
		
		if (target) {
		    syncWithTarget(target); 
        } else {
		    syncWithDockerShops(dockerShops);
        }
		
		done();
	}
};
