FROM graylog/graylog:2.5

RUN mkdir -p /etc/graylog/server/; cd /etc/graylog/server/; curl -s -O http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz; gunzip GeoLite2-City.mmdb.gz
RUN cd /usr/share/graylog/plugin; curl -s -O https://github.com/graylog-labs/graylog-plugin-slack/releases/download/3.1.0/graylog-plugin-slack-3.1.0.jar
