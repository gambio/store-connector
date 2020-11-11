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

install_dependencies() {
  inform_user "Installing dependencies"
  composer install
}

generate_metrics() {
  inform_user "Generating metrics"
  composer badge:metrics
}

generate_tests() {
  inform_user "Generating tests"
  composer badge:tests
}

generate_coverage() {
  inform_user "Generating coverage"
  composer badge:coverage
}

generate_badges() {
  inform_user "Generating badges"
  php .gitlab-ci/badge.php >> /dev/null || true
}

move_badges() {
  if [[ -f "badges/coverage.svg" ]]; then
    mv badges/coverage.svg /var/www/store/cdn/gitlab/store-connector/coverage.svg
  fi

  if [[ -f "badges/tests.svg" ]]; then
    mv badges/tests.svg /var/www/store/cdn/gitlab/store-connector/tests.svg
  fi

  if [[ -f "badges/violations.svg" ]]; then
    mv badges/violations.svg /var/www/store/cdn/gitlab/store-connector/violations.svg
  fi
}

install_dependencies
generate_metrics
generate_tests
generate_coverage
generate_badges
move_badges

inform_user "All done!"
