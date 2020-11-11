#!/bin/bash

inform_user() {
	if [[  -z "$1" ]]; then
		print_usage "Empty argument provided"
		exit 1
	fi

	local GREEN_COLOR="\033[0;32m"
	local NO_COLOR="\033[0m"

	echo -e "${GREEN_COLOR}$1${NO_COLOR}"
}

setup_environment_variables() {
  BUILD_ARG=$1
  inform_user "BUILD_ARG=${BUILD_ARG}"
}

install_dependencies() {
  if [[ "$BUILD_ARG" == "--dev" ]]
  then
    inform_user "Installing all dependencies"
    composer install --no-interaction --no-scripts --optimize-autoloader
  else
    inform_user "Installing minimal dependencies"
    composer install --no-dev --no-interaction --no-scripts --optimize-autoloader
  fi
}

setup_environment_variables $1
install_dependencies
