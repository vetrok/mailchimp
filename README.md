Main page is with users list located at '/'

System requires API key for Mailchimo, please fill it in \config\autoload\global.php

Please disable all errors, because it can block redirects from "/sync" to home page

Database structure

CREATE SCHEMA `mailchimp` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `mailchimp`.`users`
(
 `id` INT NOT NULL AUTO_INCREMENT ,
 `email` VARCHAR(255) NOT NULL ,
 `first_name` VARCHAR(255) NOT NULL ,
 `last_name` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`)
) ENGINE = InnoDB;