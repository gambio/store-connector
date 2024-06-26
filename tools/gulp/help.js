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
 * Gulp Help Task
 *
 * This task will output verbose information regarding the Gulp tasks.
 *
 * @param {Gulp} gulp Gulp instance.
 * @param {Object} $ Contains the automatically loaded gulp plugins.
 *
 * @return {Function} Returns the gulp task definition.
 */
module.exports = (gulp, $) => {
    return (done) => {
        const information = `
\n\n\n

Store Connector - Gulp Workflow
------------------------------

Docker Support:

In order to make the development and testing of the Store Connector easier, the Gulp workflow provides a task
that will automatically setup the requested GX Git branch locally and prepare the required docker containers. The shops
will run from the local-docker accessible ip: 172.17.0.1 and the default port is "8000", although this is something you
can change by providing the port argument (e.g. "gulp docker --branch 4.1_develop --port 4100", meaning that the shop
will accessible from http://172.17.0.1:3110). Additionally, you can clone and run multiple shop branches
at the same time.

PHP Version:

Older shop versions might have problems with newer PHP versions such as v7.2. The "gulp docker" provides a parameter
that can change the default PHP version installed to an older one (e.g. "gulp docker --branch 3.3_develop --php 5.6").

Target Shop:

Working with different shops is also possible with the use of the "--target" argument. While working you can pick up the preferred path so
that files are automatically synced with every change (e.g. "gulp dev --target docker/3.3_develop/shop/src").
You can omit the full path and only provide the branch name while working with shop clones generated with the "gulp docker" command and located
in the "docker" directory (e.g. "gulp dev --target 3.3_develop").

Gulp Tasks:

- "gulp archive": Creates a new "Gambio Store Connector v0.0.0.zip" archive at the root directory, based on the current "src" directory state.
- "gulp build": Builds all the assets.
- "gulp clean": Removes all dynamically generated assets.
- "gulp dev": Compiles the assets and starts the file watchers.
- "gulp doc": Generates the PHP documentation for the project.
- "gulp docker": Clones and prepares a new docker environment for the target shop.
- "gulp help": Outputs information on the repository.
- "gulp jshint": Runs jshint validation over the javascript files.
- "gulp scripts": Compiles the javascript assets.
- "gulp styles": Compiles the css assets.
- "gulp sync": Copies the content to the target shop environment.
- "gulp test": Executes the phpunit tests of the project.
- "gulp watch": Starts the file watches for the project.

\n\n\n`.replace(/\t/g, '');
        
        $.util.log(information);
        
        done();
    };
};
