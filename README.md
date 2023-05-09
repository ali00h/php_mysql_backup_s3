# PHP Mysql Backup S3
This is a simple program in php8 language to take backup from mysql or mariadb, which has the possibility of running as a docker image.
The backups taken are stored in Amazon's S3 storage and the old backups are deleted from it at the same time.

## Manual Install
1- Rename `.env.example` to `.env` and fill it with appropriate values.

Note: If you want to backup more than one database, you can seperate it with `,` in `MYSQL_DATABASES` Like: `MYSQL_DATABASES=dbname1,dbname2`

2- Go to `public` directory and run:
```
cd public
composer install
```
3- Move `public` directory to your server and call `backup.php` to execute backup.

## Docker Compose
1- Rename `.env.example` to `.env` and fill it with appropriate values OR set environment values in `docker-compose.yml`

2- Run:
```
docker-compose up -d --build
```

3- Call `backup.php` to execute backup.