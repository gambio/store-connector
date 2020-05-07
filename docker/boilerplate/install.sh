#!/bin/bash

inform_user() {

	if [[  -z "$1" ]]; then
		print_usage "Empty argument provided"
		exit 1
	fi

	local GREEN_COLOR="\033[0;32m"
	local NO_COLOR="\033[0m"

	echo -e "${GREEN_COLOR}$1${NO_COLOR}"
	sleep 0.5

}

determine_current_path() {

	inform_user "Determining current source directory where bash resides..."

	CURRENT_PATH=`dirname $BASH_SOURCE`
	CURRENT_PATH=`cd $CURRENT_PATH && pwd`

	if [ -z "$CURRENT_PATH" ] ; then
		# if the path is empty, let us immediately stop not to cause damage
		exit
	fi

}

install_shop() {

	inform_user "Installing the shop..."

	cd $CURRENT_PATH/shop/shop-installer

	./bin/run {{SHOP_URL}} store-connector-mysql-{{BRANCH_NAME}} gxdev gxdev gxdev
}

move_swap_url_script() {

	inform_user "Moving a script that replaces the store urls to the docker ones..."

	mv $CURRENT_PATH/swap_store_urls.php $CURRENT_PATH/shop/src/

}
cleanup_after_script() {

	inform_user "Installation finished, cleaning up global variables..."

	unset CURRENT_PATH

}

inform_user "Currently, the automatic installation only works with 3.11+"

determine_current_path

move_swap_url_script

install_shop

cleanup_after_script
