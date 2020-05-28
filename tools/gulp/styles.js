/* --------------------------------------------------------------
 styles.js 2020-04-30
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
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
 * @param {Gulp} gulp Gulp instance.
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = function(gulp, $) {
    const fs = require('fs');
    const path = require('path');
    
    const isDir = (file) => {
        return fs.lstatSync(file.path).isDirectory();
    };
    
    const isHtml = (file) => {
        return (path.extname(file.path) === '.html');
    };
    
    const compile = () => {
        return new Promise((resolve, reject) => {
            gulp.src([
                `src/GXModules/Gambio/Store/**/*.css`,
                `!src/GXModules/Gambio/Store/**/*.min.css`,
                `!src/GXModules/Gambio/Store/Build/**`,
                `!src/GXModules/Gambio/Store/**/Templates/**`,
            ])
                .on('finish', resolve)
                .on('error', reject)
                .pipe($.changed(`src/GXModules/Gambio/Store/Build`))
                .pipe(gulp.dest(`src/GXModules/Gambio/Store/Build`))
                .pipe($.ignore.exclude(isDir))
                .pipe($.ignore.exclude(isHtml))
                .pipe($.cleanCss())
                .pipe($.rename({suffix: '.min'}))
                .pipe(gulp.dest(`src/GXModules/Gambio/Store/Build`));
        });
    };
    
    return (done) => {
        compile()
            .catch((error) => {
                $.util.log($.util.colors.red(`Unexpected styles compilation error: ${error}`));
                // process.exit(1);
            })
            .finally(done);
    };
};
