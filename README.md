# Shorty!

This is a simple URL shortener service built on top of [Drupal 9](https://www.drupal.org/) and inspired by the [ShURLy](https://www.drupal.org/project/shurly) Drupal module.

## How to install it locally
1. Install Docker (and docker-compose separately if you are on Linux)
2. Clone git repo:
```shell
git clone git@github.com:ershovandrey/shrt.git
cd shrt
```
3. If you are on Linux, please change the php container in `.env` file - comment line `PHP_TAG=7.4-dev-macos-4.24.10` and uncomment line `PHP_TAG=7.4-dev-4.24.10`.
4. Add sh.rt to your hosts file
```shell
127.0.0.1   sh.rt
```
5. Start the docker stack (it will pull all images and start a webserver with DB)
```shell
make up
```
6. Install composer dependencies
```shell
make composer install
```
7. Check the site status
```shell
make drush status
```
8. Now you can access the URL shortener service using URL [http://sh.rt](http://sh.rt).
9. Log in as the admin - run the command, and use generated URL
```shell
make drush uli
```
or just go to [http://sh.rt/user](http://sh.rt/user) and use `admin@sh.rt`/`admin` as credentials
10. After you will finish working with the service you can stop it using command
```shell
make stop
```
or you can remove docker containers by running
```shell
make prune
```
If you will need to start the service again, just run
```shell
make up
```
and it will pull everything back.

## Functionality
There are 3 types of users supported:
  - Anonymous users:
    - Can login/register/restore password.
    - Can create an unlimited amount of Short URLs with 1 month expiration period.
  - Authenticated
    - Can create an unlimited amount of Short URLs with a maximum expiration duration of 1 year
    - Can edit own account (change email, change password)
    - Can see his saved short URLs and track visits.
  - Administrator
    - Can do everything on the site

Short URL expiration is happening during periodic cron runs. In the current implementation of the service, the admin should run the cron manually on the [Cron Settings](http://sh.rt/admin/config/system/cron) page.

Admin can control Short URLs (Shorties) using a special [admin dashboard](http://sh.rt/admin/content/shorty)

The configuration of the Short URL base path is at [Shorty settings page](http://sh.rt/admin/structure/shorty)

The user management dashboard is on the [People page](http://sh.rt/admin/people)

There is one registered user with credentials `test@example.com`/`pass` for testing purposes.

## What's missing:
- Rate limiting
- Spam prevention
- Danger URL filtering
- Caching the redirect response in any external service like Varnish
- Integration with analytics services
- Ability to enter custom short URL
- You name it :).
