#!/usr/bin/env bash

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

hide_office_network_from_docker() {

	inform_user "Hiding the internal office network from docker..."

	NAMESERVER_LINE="nameserver $(ip -4 addr show docker0 | grep -Po 'inet \K[\d.]+')"
	NAMESERVER_FILE=/etc/resolv.conf
	grep -qF "$NAMESERVER_LINE" "$NAMESERVER_FILE" || echo "$NAMESERVER_LINE" | sudo tee --append "$NAMESERVER_FILE"

}

inform_user "Configuring operating system (root permissions may be necessary) ..."

hide_office_network_from_docker

inform_user "Starting containers..."