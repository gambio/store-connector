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
 * @todo There is a known phpdox issue with php 7.2 --> https://github.com/theseer/phpdox/issues/315
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
    return $.shell.task(['bash docs/generate-docs.sh']);
};
