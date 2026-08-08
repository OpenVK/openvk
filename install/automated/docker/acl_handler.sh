#!/bin/sh

trap 'exit 0' TERM

while :
do
    chown -R 33:33 /opt/openvk/storage
    chown -R 33:33 /opt/openvk/tmp/api-storage/audios
    chown -R 33:33 /opt/openvk/tmp/api-storage/photos
    chown -R 33:33 /opt/openvk/tmp/api-storage/videos

    sleep 600
done
