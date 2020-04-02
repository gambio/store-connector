#!/bin/bash

echo "Configuring operating system (root permissions required) ..."

NAMESERVER_LINE='nameserver 172.21.0.0'
NAMESERVER_FILE=/etc/resolv.conf
grep -qF "$NAMESERVER_LINE" "$NAMESERVER_FILE" || echo "$NAMESERVER_LINE" | sudo tee --append "$NAMESERVER_FILE"

VM_LINE='vm.max_map_count=262144'
VM_FILE=/etc/sysctl.conf
grep -qF "$VM_LINE" "$VM_FILE" || echo "$VM" | sudo tee --append "$VM_FILE"

sudo sysctl -w vm.max_map_count=262144

docker-compose up
