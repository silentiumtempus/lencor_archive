# lencor_archive
Lencor archive application

ElasticSearch is requried to be installed and configured to run this application


How to install application

You need apache2, php=>7.0.24, mysql-server=>5.5, elasticsearch=5.5, composer=>1.2 installed and configured properly.
MySQL Database with specific user to access is advisable to have.
You may also require phpmyadmin to maintain the application if necessary.

1. Clone git repository to your apache website folder:
**git clone https://github.com/silentiumtempus/lencor_archive**

2. cd to downloaded project folder.

3. Check project folder permissions, it's implied that apache and composer are able to write in this directory, so perform needful **chmod** and **chown** operations if required.

4. Update the **parameters.yml.dist** file replacing values with your enviroment configuration parameters.

5. run "**composer install**" (in case of failure during cache:clear operation you may ignore it).

6. run "**php bin/console doctrine:schema:create**" to initialize db structure in new schema.

7. run "**php bin/console fos:elastica:populate**" to populate application search cache.

8. run "**php bin/console cache:clear**".

9. run "**composer update**".

10. run "**php bin/console fos:user:create**" to create an internal application user.

11. Check the website index page at:
 - http://website.url/web/app_dev.php/welcome_index for dev environment
 - http://website.url/web/app.php/welcome_index for prod environment

12. Enjoy!

You need to be authorized to perform operations, for this use the Login button at the right-top of the application screen.
