FROM bref/php-80-console

COPY ./ /var/task

CMD ["console"]