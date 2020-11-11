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

generate_metrics() {
    inform_user "Geneating metrics"
    composer metrics
}

generate_metrics

inform_user "All done!"
