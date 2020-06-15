#!/bin/bash

./prestart.sh

export DOCKER_HOST_IP="$(ip -4 addr show docker0 | grep -Po 'inet \K[\d.]+')"

docker-compose up
