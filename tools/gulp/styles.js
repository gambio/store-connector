/* --------------------------------------------------------------
 gulp_styles.js 2018-11-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Styles Task
 *
 * This task will handle the compilation of CSS files.
 *
 * @param {Gulp} gulp Gulp Instance
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = function(gulp, $) {
	const fs = require('fs');
	const path = require('path');
	const environment = require('./lib/environment');
	
	const isDir = (file) => {
		return fs.lstatSync(file.path).isDirectory();
	};
	
	const isHtml = (file) => {
		return (path.extname(file.path) === '.html');
	};
    
    const compile = (variant, vendorName, moduleName) => {
        return new Promise((resolve, reject) => {
            gulp.src([
                `${variant}/GXModules/${vendorName}/${moduleName}/**/*.css`,
                `!${variant}/GXModules/${vendorName}/${moduleName}/**/*.min.css`,
                `!${variant}/GXModules/${vendorName}/${moduleName}/Build/**`,
                `!${variant}/GXModules/${vendorName}/${moduleName}/**/Templates/**`,
            ])
                .on('finish', resolve)
                .on('error', reject)
                .pipe($.changed(`${variant}/GXModules/${vendorName}/${moduleName}/Build`))
                .pipe(gulp.dest(`${variant}/GXModules/${vendorName}/${moduleName}/Build`))
                .pipe($.ignore.exclude(isDir))
                .pipe($.ignore.exclude(isHtml))
                .pipe($.cleanCss())
                .pipe($.rename({suffix: '.min'}))
                .pipe(gulp.dest(`${variant}/GXModules/${vendorName}/${moduleName}/Build`));
        });
    };
	
	return (async) => {
		const variant = environment.getArgument('variant') || 'src';
		
		const vendorNames = fs.readdirSync(variant + '/GXModules')
			.filter(file => fs.statSync(path.join(variant + '/GXModules', file)).isDirectory());
		
		const compilations = [];
		
		for (let vendorName of vendorNames) {
			const moduleNames = fs.readdirSync(variant + '/GXModules/' + vendorName)
				.filter(file => fs.statSync(path.join(variant + '/GXModules', vendorName, file)).isDirectory());
			
			for (let moduleName of moduleNames) {
			    compilations.push(compile(variant, vendorName, moduleName));
			}
		}
		
		Promise.all(compilations)
            .then(() => async())
            .catch((error) => {
                $.util.log($.util.colors.red(`Unexpected styles compilation error: ${error}`));
                process.exit(1);
            });
	};
};
