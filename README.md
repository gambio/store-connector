### **For developers: update the *phpdocumentor* package to a stable version (>=3.0) once it released and set `minimum-stability` to `stable` in the package.json file** ###
---

# Gambio Store Connector
> Store integration module for GX shops. 

<img src="https://store.gambio.com/cdn/gitlab/store-connector/tests.svg" height="22" alt="Tests"> <img src="https://store.gambio.com/cdn/gitlab/store-connector/coverage.svg" height="22"  alt="Coverage"/> <img src="https://store.gambio.com/cdn/gitlab/store-connector/violations.svg" height="22" alt="Violations">

The Gambio Store Connector enables the integration of the Gambio Store service into GX shops. This repository holds all  
the code required for the module, along with all the variants included in the final released archive. 

## Installation

```sh
yarn configure 
```

## Get started

You will be needing a GX shop setup in order to use this repository. The gulp workflow offers an easy way to clone and 
use shops by using the docker task. 

```sh
gulp docker --branch 4.0_release_v4.0.2 --port 4100 --php 7.2
```

The command will need some time to complete as it will clone, setup and bootstrap the docker images for the shop 
instance. Follow the provided instructions to get the Docker containers started.

## Building archive

The archive building procedure is based on the current state of the `src` directory.

```sh
gulp archive
```

## Additional Help 

Execute the `gulp help` command to get more information on the available tasks and development workflows.

Older shop versions will not work with the default PHP v7.2, you will therefore have to choose a compatible version.

You can change the default PHP version by providing the "--php" option, when cloning a new branch with the `gulp docker`
command (e.g. "gulp docker --branch 3.3_develop --php 5.6"). 

If the docker environment is already created with a non compatible version you can still change it by deleting the 
existing server container and image with the use of the `remove.sh` script and the 
`docker rmi {image}` command. You can re-create the containers by running the `start.sh` script again. 

You will find a list of [all supported PHP images on Docker Store](https://hub.docker.com/_/php).

During the shop installation you will have to enter the database information of the Docker container setup. Those can 
always configured beforehand in the `docker-compose.yml` file, located in the root of each Docker setup. 

Bear in mind that the host is always the name of the container, e.g. `hub-connector-mysql-4.1_develop`. 
  
