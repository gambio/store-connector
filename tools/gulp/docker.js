/* --------------------------------------------------------------
 docker.js 2020-04-30
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
    const execSync = require('child_process').execSync;
    const environment = require('./lib/environment');
    
    const replace = (filePath, data) => {
        if (!fs.existsSync(filePath)) {
            throw new Error('Target file does not exist at ' + filePath);
        }
        
        let contents = fs.readFileSync(filePath, 'utf8');
        
        for (let key in data) {
            contents = contents.replace(new RegExp('{{' + key + '}}', 'gi'), data[key]);
        }
        
        fs.writeFileSync(filePath, contents, 'utf8');
    };
    
    return (done) => {
        const shopBranchName = environment.getArgument('branch') || '4.1_develop'
        
        if (!shopBranchName) {
            throw new Error('This gulp task requires a shop branch (example: gulp docker 4.1_develop).');
        }
        
        if (shopBranchName === 'boilerplate') {
            throw new Error('"Boilerplate" is a reserved directory, please use an existing GX shop branch name.');
        }
        
        const basePath = path.resolve(__dirname, '../../docker');
        const boilerplatePath = basePath + '/boilerplate';
        const clonePath = basePath + '/' + shopBranchName;
        
        if (!fs.existsSync(boilerplatePath)) {
            throw new Error('The "boilerplate" path could not be found at' + boilerplatePath);
        }
        
        if (fs.existsSync(clonePath)) {
            throw new Error('The requested branch is already cloned at ' + clonePath
                + ', please remove it and start again');
        }
        
        fs.copySync(boilerplatePath, clonePath);
        
        const phpVersion = environment.getArgument('php') || '7.2';
        const serverPort = environment.getArgument('port') || '8000';
        const mysqlPort = String(parseInt(serverPort) + 1);
        
        if (['5.6', '7.0', '7.1', '7.2', '7.3', '7.4'].includes(phpVersion) === false) {
            throw new Error('The provided PHP version ' + phpVersion + ' is not supported by this Docker '
                + 'configuration.');
        }
        
        replace(clonePath + '/server/Dockerfile', {
            phpVersion
        });
        
        replace(clonePath + '/docker-compose.yml', {
            branch: shopBranchName,
            phpVersion: phpVersion,
            serverPort,
            mysqlPort
        });
        
        replace(clonePath + '/exec.sh', {
            branch: shopBranchName
        });
        
        
        replace(clonePath + '/exec.sh', {
            branch: shopBranchName
        });
        
        replace(clonePath + '/install.sh', {
            branch: shopBranchName
        });
        
        replace(clonePath + '/swap_store_urls.php', {
            branch: shopBranchName
        });
        
        [
            'chmod +x *.sh',
            'git clone git@sources.gambio-server.net:gambio/gxdev.git shop',
            'cd shop && git checkout ' + shopBranchName,
            'touch shop/src/.dev-environment',
            'cp shop/src/includes/configure.release.php shop/src/includes/configure.php',
            'touch shop/src/includes/configure.org.php',
            'touch shop/src/admin/includes/configure.php',
            'touch shop/src/admin/includes/configure.org.php',
            'rm -f shop/package-lock.json shop/yarn.lock',
            'sed -i "s/npm install/yarn install/g" shop/package.json',
            'cd shop && git config core.fileMode false',
            'cd shop && sed -i \'s/composer install/composer install --ignore-platform-reqs/\' package.json',
            'cd shop && yarn run configure',
            'chmod -R 777 shop/src'
        ]
            .forEach(command => execSync(command, {cwd: clonePath}));
        
        [
            'git clone git@sources.gambio-server.net:interne-toolkueche/shop-installer.git shop/shop-installer',
            'cd shop/shop-installer && yarn'
        ]
            .forEach(command => execSync(command, {cwd: clonePath}));
        
        replace(clonePath + '/install.sh', {
            BRANCH_NAME: shopBranchName,
            SHOP_URL: 'http://172.17.0.1:' + serverPort + '/gambio_installer/'
        });
        
        
        const usage = `
The shop clone was completed successfully!

Execute the "start.sh" script from within "${clonePath}" to get the Docker containers started.

Once they're running, execute the "install.sh" script from within "${clonePath}" to get the shop installed.
		`;
        
        console.info(usage);
        
        done();
    };
};
