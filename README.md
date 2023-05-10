# PHP Mysql Backup S3
This is a simple program in php8 language to take backup from mysql or mariadb, which has the capability of running as a docker image.
The backups taken are stored in Amazon's S3 storage, and the old backups are deleted from it at the same time.

## Environment Variables
```
MYSQL_USERNAME=root
MYSQL_PASSWORD=Pm123456
MYSQL_DATABASES=test_db
MYSQL_HOST=host.docker.internal
MYSQL_PORT=3306
AWS_AccessKey=
AWS_SecretKey=
AWS_Bucket=
AWS_Endpoint=
AWS_Region=
AWS_BACKUP_DIRECTORY=dbbackup/mysql/
AWS_MAX_BACKUP_COUNT_FOR_EACH_DB=30
TIME_ZONE=America/LosAngeles
BACKUP_URL_SecretKey=
```
| ENV | Description |
| --- | --- |
| `MYSQL_USERNAME` | Database username |
| `MYSQL_PASSWORD` | Database password |
| `MYSQL_DATABASES` | List of **database names** which separated by `,`. For example `MYSQL_DATABASES=dbname1,dbname2` |
| `MYSQL_HOST` | Database host name or IP |
| `MYSQL_PORT` | Database port |
| `AWS_AccessKey` | AWS S3 AccessKey |
| `AWS_SecretKey` | AWS S3 SecretKey |
| `AWS_Bucket` | **AWS S3 Backet name** in which backups stored |
| `AWS_Endpoint` | **AWS S3 Endpoint** like `s3.amazonaws.com` |
| `AWS_Region` | **AWS S3 Region** like `us‑east‑2` |
| `AWS_BACKUP_DIRECTORY` | Prefix directory in Backet in which backups stored |
| `AWS_MAX_BACKUP_COUNT_FOR_EACH_DB` | Maximum number of backups for each databases. New backup would be replaced with oldest backup. |
| `TIME_ZONE` | Your time zone for creating backup file name. |
| `BACKUP_URL_SecretKey` | **(Optional)** If this variable is not empty, you should call `backup.php?sk=<SecretKey>` to run backups. |

## Docker
Create .env file with environment variables. and run this command:
```
docker run -d -p 8040:8080 ali00h/php_mysql_backup_s3
```



## Docker Compose
1- Rename `.env.example` to `.env` and fill it with appropriate values OR set environment values in `docker-compose.yml`

2- Run:
```
docker-compose up -d --build
```

3- Call `http://localhost:8040/backup.php` to execute backup.

## Manual Install
1- Rename `.env.example` to `.env` and fill it with appropriate values.

Note: If you want to backup more than one database, you can seperate it with `,` in `MYSQL_DATABASES` Like: `MYSQL_DATABASES=dbname1,dbname2`

2- Go to `public` directory and run:
```
cd public
composer install
```
3- Move `public` directory to your server and call `backup.php` to execute backup.

## Development
Just run this command:
```
docker-compose -f docker-compose-development.yml up -d --build
```
And run this url:
```
http://localhost:8040/backup.php
```