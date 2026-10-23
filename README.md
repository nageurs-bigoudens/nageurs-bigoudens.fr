Les Nageurs Bigoudens - Club, Ecole de natation et Perfectionnement
https://nageurs-bigoudens.fr/


Composer est nécessaire:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install


Renseigner le fichier config/config.ini:
commencer par copier le "template"
cp config/config-template.ini config/config.ini


Base de données:
création une BDD vide: CREATE DATABASE nageurs CHARACTER SET utf8mb4;
droits à accorder à l'utilisateur BDD avec GRANT: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP et ALTER
