#!/bin/bash
docker stop weat
docker rm weat
docker build -t weat . && \
  docker create --name weat \
    -p 6666:80 \
    -v /opt/geoip:/opt/geoip \
    -v /home/er0k/dev/weat:/var/www \
    weat && \
docker start weat && \
docker logs -f weat
