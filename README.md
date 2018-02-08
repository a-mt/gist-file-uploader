# Gist file uploader

## Install

### Install php-curl

Check if curl module is available

    ls -la /etc/php5/mods-available/

If it is, enable the curl module

    sudo php5enmod curl

If not, install it

    sudo apt-get update
    sudo apt-get install php5-curl

Restart Apache

    service apache2 restart

### Create a Github app

Go to `Settings` (upper right) > `Developer settings` > `New OAuth App`

* Choose an application name
* Type the full URL of your app's website
* In "Authorization callback URL" type your app's website followed by `/auth`
* Copy the Client ID and Client Secret and change the constants in `inc.config.php`

### Update your max file size

You can update your `post_max_size` and `upload_max_filesize` to upload your bigger file. ([Git files soft limit is 50M](https://help.github.com/articles/conditions-for-large-files/))

Retriieve `post_max_size` and `upload_max_filesize` values :

    php -r 'echo ini_get("post_max_size") . "\n";'
    php -r 'echo ini_get("upload_max_filesize") . "\n";'

Retrieve your php.ini path :

    php --ini

Update the values and restart Apache

    service apache2 restart