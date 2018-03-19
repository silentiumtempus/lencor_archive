# lencor_archive
Lencor archive application is based on PHP Symfony framework

ElasticSearch is required to be installed and configured to run this application


How to install application

You need Apache2, PHP=>7.1.x, MySQL-server=>5.5, ElasticSearch=5.x, Composer=>1.2, Yarn=>1.5.x, NodeJS=8.9.x installed and configured properly.
MySQL Database with specific user to access is advisable to have.
You may also require phpmyadmin to maintain the application if necessary.

1. Clone git repository to your apache website folder:
**git clone https://github.com/silentiumtempus/lencor_archive**

2. cd to downloaded project folder.

3. Check project folder permissions, it's implied that apache and composer are able to write in this directory, so perform needful **chmod** and **chown** operations if required.

4. Copy **.env.dist** to new **.env** file and them update the **.env**  and **config/packages/parameters.yml** files replacing values with your enviroment configuration parameters.

5. run "**composer install**" (in case of failure during cache:clear operation you may ignore it).

6. run "**php bin/console doctrine:schema:create**" to initialize db structure in new schema.

7. run "**php bin/console fos:elastica:populate**" to populate application search cache.

8. run "**php bin/console cache:clear**".

9. run "**composer update**".

10. run "**./node_modules/.bin/encore dev**". 

11. run "**php bin/console fos:user:create**" to create an internal application user.

12. run "**php bin/console fos:user:promote**" and add **ROLE_ADMIN** for previously created user.

13. Check the website index page at:
 - http://website.url/web/app_dev.php/entries for dev environment
 - http://website.url/web/app.php/entries for prod environment 

You need to be authorized to perform operations, for this use the Login button at the right-top of the application screen.


## Additional client configuration

[File share links](https://github.com/silentiumtempus/lencor_archive/blob/master/app/Resources/doc/file_links.md)
