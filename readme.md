# TED Reader API

TED Reader API is an open-source backend api running on the [Lumen](http://lumen.laravel.com/docs) framework.

## Installation

Configure your .env. Accessing the FTP server is explained [here](http://ted.europa.eu/TED/misc/legalNotice.do).

    TED_FTP=
    TED_USERNAME=
    TED_PASSWORD=

Prep the database and download the packages.
Downloading the packages might take quite a long time depending on how far back you want to go. 

    php artisan migrate  # migrate the database 
    php artisan ted:categories  # import the categories this could take a couple of seconds.
    php artisan ted:monthly # gets monthly packages specify year with --year default is current year
    php artisan ted:daily # gets daily packages.
    
Now that we have all the packages we can convert them to json ( cuts the size footprint by 50 to 75 percent. ).

    php artisan ted:convert # you can specify a package with --package to only convert one instead of all of them.

All of our packages have been converted so its time to import them into the database.

    php artisan ted:import # you can specify a package with --package to only import one instead of all of them.

The importing is going to take a very long time when you are importing years worth of more then 2000 notices a day.

While you are busy importing you might as well set up cronjobs I suggest the following; 9 AM each morning tuesday to saturday.

    0 9 * * 2 cd /path/to/api/root && php artisan ted:daily
    0 9 * * 3 cd /path/to/api/root && php artisan ted:daily
    0 9 * * 4 cd /path/to/api/root && php artisan ted:daily
    0 9 * * 5 cd /path/to/api/root && php artisan ted:daily
    0 9 * * 6 cd /path/to/api/root && php artisan ted:daily

also you might want to take a look at app/bootstrap.php to lock down the cors header.

## License

The TED Reader is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
