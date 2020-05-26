# from root of project
docker build -f Docker/php/dockerfile -t php:7.4-apache .

docker run --detach -v /home/nigel/eclipse-workspace/Pleb:/var/www/html --name PlebPHP php:7.4-apache