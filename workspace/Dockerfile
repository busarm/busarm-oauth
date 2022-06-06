FROM webdevops/php:8.0-alpine

# Start as root
USER root

# Updated and Add packages
RUN set -xe; \
    apk update && \
    apk add --no-cache supervisor && \
    apk add --no-cache unzip && \
    apk add --no-cache mysql mysql-client && \
    apk add --no-cache rsync

# Clean up
RUN rm -rf /tmp/* /var/tmp/* && \
    rm -rf /var/log/lastlog /var/log/faillog

WORKDIR /var/www

# ADD ./ .

# Init script
# ADD ./workspace/init.sh /opt/init.sh
# RUN sed -i 's/\r//g' /opt/init.sh
# ENTRYPOINT [ "/opt/init.sh" ]

CMD [ "/var/www//workspace/init.sh" ]
