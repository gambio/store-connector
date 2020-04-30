/* --------------------------------------------------------------
 gulp_scripts.js 2018-11-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2018 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

'use strict';

/**
 * Gulp Scripts Task
 *
 * This task will concatenate and minify the javascript files. The final files will be
 * placed in the GXModules/Gambio/Hub/Build directory.
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
		return path.extname(file.path) === '.html';
	};
	
	const compile = (src, dest) => {
	    return new Promise((resolve, reject) => {
            gulp.src([
                `${src}/**/*.js`,
                `!${src}/**/*.min.js`,
                `!${src}/Build/**`,
                `!${src}/**/Templates/**`
            ])
                .on('finish', resolve)
                .on('error', reject)
                .pipe($.changed(dest))
                .pipe($.sourcemaps.init())
                .pipe($.babel({presets: ['@babel/env']}))
                .pipe($.sourcemaps.write())
                .pipe(gulp.dest(dest))
                .pipe($.ignore.exclude(isDir))
                .pipe($.ignore.exclude(isHtml))
                .pipe($.uglify().on('error', $.util.log))
                .pipe($.rename({suffix: '.min'}))
                .pipe(gulp.dest(dest));
        });
	};
	
	return (async) => {
		const variant = environment.getArgument('variant') || 'src';
		
		const vendorNames = fs.readdirSync(variant + '/GXModules')
			.filter(file => fs.statSync(path.join(variant + '/GXModules', file)).isDirectory());
		
		const compilations = [];
		
		for (let vendorName of vendorNames) {
			const moduleNames = fs.readdirSync(variant + '/GXModules/' + vendorName)
				.filter(file => fs.statSync(path.join(variant + '/GXModules/', vendorName, file)).isDirectory());
			
			for (let moduleName of moduleNames) {
			    const src = path.join(variant, 'GXModules', vendorName, moduleName);
			    const dest = path.join(variant, 'GXModules', vendorName, moduleName, 'Build');
				compilations.push(compile(src, dest));
			}
		}
		
		Promise.all(compilations)
            .then(() => async())
            .catch((error) => {
                $.util.log($.util.colors.red(`Unexpected scripts compilation error: ${error}`));
                process.exit(1);
            });
	};
};