# lencor_archive
Lencor archive application is based on PHP Symfony framework

ElasticSearch is required to be installed and configured to run this application


How to install application

You need Apache2, PHP=>7.1.3, MySQL-server=>5.5, ElasticSearch=5.x, Composer=>1.2, Yarn=>1.5.x, NodeJS=8.9.x installed and configured properly.
MySQL Database with specific user to access is advisable to have.
You may also require phpmyadmin to maintain the application if necessary.

1. Configure webserver to allow rewrite rules following .htaccess file 

    <Directory /var/www/project/>
    
        # enable the .htaccess rewrites
        
        AllowOverride All
        
        Order Allow,Deny
        
        Allow from all
                
    </Directory>    

2. Clone git repository to your apache website folder:
**git clone https://github.com/silentiumtempus/lencor_archive**

3. cd to downloaded project folder.

4. Check project folder permissions, it's implied that apache and composer are able to write in this directory, so perform needful **chmod** and **chown** operations if required.

5. Copy **.env.dist** to new **.env** file and them update the **.env**  and **config/packages/parameters.yml** files replacing values with your enviroment configuration parameters.

6. run "**composer install**" (in case of failure during cache:clear operation you may ignore it).

7. run "**php bin/console doctrine:schema:create**" to initialize db structure in new schema.

8. run "**php bin/console fos:elastica:populate**" to populate application search cache.

9. run "**yarn install**". 

10. run "**./node_modules/.bin/encore dev**"

11. run "**php bin/console fos:user:create**" to create an internal application user.

12. run "**php bin/console fos:user:promote**" and add **ROLE_ADMIN** for previously created user.

13. Check the website index page at:
 - http://website.url/public for dev environment
 - http://website.url/public for prod environment 

You need to be authorized to perform operations, for this use the Login button at the right-top of the application screen.
