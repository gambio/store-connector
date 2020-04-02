/* --------------------------------------------------------------
 gulpfile.js 2020-04-02
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2020 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */


/**
 * Gulp Configuration File
 *
 * Examples:
 *
 * > user@pc:~$ gulp doc
 * > user@pc:~$ gulp dev
 * > user@pc:~$ gulp build
 *
 * It is also important that each task domain uses more or less the same task names
 * for common operations such as JavaScript concatenation, SASS compilation etc.
 *
 * Recommended Task Names:
 *
 *   "scripts" - Manipulate JavaScript Files
 *   "styles" - Manipulate CSS/SCSS Files
 *   "legacy" - Manipulate Old Files
 *   "doc" - Generate Documentation
 *   "test" - Trigger Test Execution
 *   "clean" - File & Directory Removal
 *   "build" - Final State Preparation
 *   "dev" - Initiate File Watchers + FTP for Development
 *   "watch" - Watch directories and files.
 *   "vendor" - Import external dependencies into the project (mostly bower components).
 *   "ftp" - Upload assets files to the FTP server.
 *   "templates" - Manipulate Smarty templates (might also clear the templates_c contents).
 *   "coverage" - Produce code coverage documents for unit tests.
 *
 * This file will use the "domain_handler" module in order to automatically register
 * the available tasks of each domain. You just have to create a new task file inside
 * a domain's directory with the "gulp_{domain}_{task}.js" naming convention.
 */

'use strict';

// ----------------------------------------------------------------------------
// INITIALIZE GULP + MODULES
// ----------------------------------------------------------------------------

/**
 * Require Gulp
 *
 * @type {Gulp}
 */
const gulp = require('gulp');

/**
 * Load all gulp modules under the "$" variable. Custom modules that do not have the "gulp-" prefix
 * will need to be loaded manually, wherever required.
 *
 * @type {Function}
 */
const $ = require('gulp-load-plugins')();

/**
 * Banner with Gulp workflow information.
 *
 * @type {String}
 */
const banner = `
\n\n
Gulp workflow brought to you by ...

  ____                 _     _          ____           _     _   _
 / ___| __ _ _ __ ___ | |__ (_) ___    / ___|_ __ ___ | |__ | | | |
| |  _ / _\` | '_ \` _ \\| '_ \\| |/ _ \\  | |  _| '_ \` _ \\| '_ \\| |_| |
| |_| | (_| | | | | | | |_) | | (_) | | |_| | | | | | | |_) |  _  |
 \\____|\\__,_|_| |_| |_|_.__/|_|\\___/   \\____|_| |_| |_|_.__/|_| |_|
 
                                                   Copyright © ${new Date().getFullYear()}


* This gulp configuration has a minimum requirement of NodeJS v10.

* Execute 'gulp' to build all the dynamic resources of the project.

* Execute 'gulp help' for more information about the workflow and the available tasks.

* Execute 'gulp dev' build the dynamic resources and start the file watchers.

* Folder structure changes might break the 'dev' tasks due to watcher caching. Run the 'dev' task again.

* Remember to restart the 'dev' task every 3 ~ 4 hours.

\n\n\n`;

$.util.log($.util.colors.dim(banner));

// ----------------------------------------------------------------------------
// GULP ERROR HANDLING
// ----------------------------------------------------------------------------

const gulpSrc = gulp.src;

gulp.src = function() {
    return gulpSrc.apply(gulp, arguments)
        .pipe($.plumber({
            errorHandler: $.notify.onError("Error: <%= error.message %>")
        }));
};

// ----------------------------------------------------------------------------
// DEFINE TASKS
// ----------------------------------------------------------------------------

[
    'archive',
    'build',
    'clean',
    'composer',
    'dev',
    'doc',
    'docker',
    'help',
    'jshint',
    'scripts',
    'styles',
    'sync',
    'test',
    'watch'
]
    .forEach(task => gulp.task(task, require('./tools/gulp/' + task)(gulp, $)));

gulp.task('default', ['build']);
