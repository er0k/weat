#!/bin/bash
docker stop weat
docker rm weat
docker build -t weat . && \
  docker create --name weat \
    --log-driver syslog \
    --log-opt mode=non-blocking \
    --log-opt tag=docker-weat \
    --log-opt syslog-address=udp://localhost:514 \
    --log-opt syslog-facility=daemon \
    -p 6666:80 \
    -v /opt/geoip:/opt/geoip \
    -v /home/er0k/dev/weat:/var/www \
    --restart unless-stopped \
    weat && \
docker start weat
