# Retail ERP

### Requirements

- PHP 8.2
- Nodejs 18.18
- Other [Laravel requirements](https://laravel.com/docs/9.x/deployment#server-requirements)

### Installation

- Setup .env variables (Especially SANCTUM_STATEFUL_DOMAINS should be set. The value should be the domain name without 'https://' and a trailing slash. And `LIST_OF_WEB_APP_URLS_AND_KEYS` should be updated in all the sites. And, also specify the key `SITE_IDENTIFIER_KEY=`)

- Deploy once from the envoyer

- Public images: Create symlink in the envoyer

- Generate Key: `php artisan key:generate`

- Set the `LOG_CHANNEL` to `production_stack` and specify the `LOG_SLACK_WEBHOOK_URL` if you wish to receive log entries in Slack.

- Set the `JOB_FAILURE_SLACK_WEBHOOK_URL` & `JOB_FAILURE_SLACK_CHANNEL` to get queue job failure notification in Slack.

- Install [redis](https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-redis-on-ubuntu-22-04) and [phpredis](https://github.com/phpredis/phpredis):
    ```shell
      sudo apt install -y redis-server
      sudo nano /etc/redis/redis.conf
   ```
    - Configure redis as follows:

    ```editorconfig
    . . .

    # If you run Redis from upstart or systemd, Redis can interact with your
    # supervision tree. Options:
    #   supervised no      - no supervision interaction
    #   supervised upstart - signal upstart by putting Redis into SIGSTOP mode
    #   supervised systemd - signal systemd by writing READY=1 to $NOTIFY_SOCKET
    #   supervised auto    - detect upstart or systemd method based on
    #                        UPSTART_JOB or NOTIFY_SOCKET environment variables
    # Note: these supervision methods only signal "process is ready."
    #       They do not enable continuous liveness pings back to your supervisor.
    supervised systemd

    . . .
    ```

- Install [pickle](https://github.com/FriendsOfPHP/pickle):
```shell
wget https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar
```
- Now install `phpredis` and use all the default settings.
```shell
sudo php pickle.phar install redis
```
- Setup Horizon(Add Daemon Process), and set .env variables accordingly.

- We Need To Install The gRPC Extension For The Firebase `sudo add-apt-repository ppa:ondrej/php && sudo apt install php8.2-gRPC`

- We need to install the imagick extension For the QRcode. `sudo add-apt-repository ppa:ondrej/php && sudo apt install php8.2-imagick`

- We need to install the and gd extension For the QRcode and php spreadsheet. `sudo add-apt-repository ppa:ondrej/php && sudo apt-get install php8.2-gd`

- We are maintaining max execution time (1200) and max file upload (30MB) setting from the forge for production.

- Timezone changed to MYT:
    - Forge > Server > Settings > Server Settings (Update the timezone)
    - (If you have done the change via forge, no need to follow this line) Server level - `sudo timedatectl set-timezone Asia/Kuala_Lumpur` (Command to check current timezone - `timedatectl`)
    - Laravel - application timezone is set to `Asia/Kuala_Lumpur` in the config file.
    - MySQL and PHP timezone depends on the server timezone. Just reload PHP FPM & MySQL services.
    - If there is a separate server for MySQL, you need to set the server timezone accordingly.

- Setup Scheduler from the forge

- Seed data using **one** of the following:
    - For production, run `php artisan db:seed --class=StaticDataSeeder` to seed static data required to run the system.
    - DB Seed: `php artisan db:seed` (Run if you need auto-generated sample data)
    - For seeding data for mobile POS API, run `php artisan db:seed --class=MobileApiSeeder` to seed 10k records.

- When using Envoyer:
    - **Important**: We cannot create the folders inside the `storage` directory using the .gitignore file because Envoyer ignores that directory and just symlinks it to the `storage` directory at the root of the project. Either we need to create those directories manually there or we should use composer scripts to create the folders, if required, after each install command.

### OCI (Oracle) Object Storage
- Please set `OCI_*` .env variables before enabling the 'oci' disk for file storage.
- You may set the `MEDIA_DISK` .env variable to 'oci' to use OCI Object Storage with Spatie's medialibrary package.


### Things to test
- Add or edit functionality of any module
- File uploads
- Import records to test Horizon + Queued jobs
- Emails
- File export
- PDF generation in Barcode module
- POS connection
- Automated backups
- Logs, Slack notifications, and Sentry

### Identification of the site by POS

Cashier needs to enter the configuration key before logging in. And POS makes an API call to one of the sites to fetch the URL of the site associated with the specified configuration key. Currently, we are adding all the keys to all the sites but long term we will take this to a separate site.

### Laravel Pulse

We are using [Laravel Pulse](https://pulse.laravel.com/) to track application's performance and usage.

- It can be accessed through the URL: DOMAIN/pulse
- Only super admin can access Pulse dashboard when the application is in production mode.
- You can specify `PULSE_DB_*` .env variables to use another DB connection.

### PHP version upgrade steps

1. Server level: Install new versin of PHP on the server from Forge UI. And set default to it on CLI as well as FPM.
1. Server level: Check if PHP redis extension needs to be updated.
1. Server level: memory limit, max upload file size, etc. config changes as per the README.
1. Server level: Install required PHP extensions for the application to run. (Mostly forge takes care of this.)
1. Site level: Update the PHP version from the Meta tab of the Forge UI.
1. (Optional) Site level: Update nginx site config to handle requests using the new php-fpm version
1. Server level: Reload nginx
1. Server level: Update the Scheduler and Daemons paths to reflect new PHP version.
1. Envoyer Site Level: Update Server -> Select new PHP Version
1. Deploy new code from Envoyer

### The IOI City Mall Sales File Generator
- When server migration time somehow horizon not working that time ioi city mall files not generate so you need to run the command manually.
- File Generate Command `php artisan generate:ioi-city-mall-sales-files date(Y-m-d)`
- File Upload Command `php artisan upload:ioi-city-mall-sales-files`

### The TRX Mall Upload Sales Data
- When server migration time somehow horizon not working that time trx city mall files not send so you need to run the command manually.
- File Generate Command `php artisan trx:send-sales --date=(Y-m-d)`