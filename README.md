# Retail ERP

### Only for local setup

First read the [Technical Document](TechnicalDocument.md) file and then setup the project.

### Documentation
- https://www.notion.so/ERP-Development-Specifications-5a45ec1eaf4945a39c16cc5b777c6b12
We use Notion to record all tasks and track progress. Bugs/features/updates all should be recorded in Notion.

1) If you're discussing something with a team member or decision makers, record the important notes/ideas in a Notion task.
2) If updates in your code affects other files,
    i) Either make the necessary changes right away when it's needs to be deployed together or short in nature
    ii) Add a Notion task with details and link it with the PR both ways.

TLDR: There is no harm in creating lots of Notion tasks even if they need to be archived later on. But a lot of potential loss of missing important stuff if one is lazy or over confident on memory.

### Technical

The information about the development process and reference details are recorded in [this file](DeveloperGuidelines.md).

### Requirements

- PHP 8.2
- Nodejs 18.18
- Other [Laravel requirements](https://laravel.com/docs/9.x/deployment#server-requirements)

### Installation

- Clone the repo:
`git clone [REPO_URL] [DIRECTORY_NAME]`

- Create `.env` file from the example file:
`php -r "file_exists('.env') || copy('.env.example', '.env');"`

- Setup .env variables (Especially SANCTUM_STATEFUL_DOMAINS should be set. The value should be the domain name without 'http://' and a trailing slash. And `LIST_OF_WEB_APP_URLS_AND_KEYS` should be updated in all the sites. And, also specify the key `SITE_IDENTIFIER_KEY=`)

- Install the dependencies: `composer install`

- Generate Key: `php artisan key:generate`

- DB migrate: `php artisan migrate`

- Public images: `php artisan storage:link`

- NPM: `npm install`

- `npm run dev` and start developing...

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
    - Restart `redis`
    ```shell
    sudo systemctl restart redis.service
    ```

    - Test `redis`
    ```shell
    redis-cli
    ```
    - In the prompt that follows, test connectivity with the `ping` command and you should get `pong` response.

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

- To allow to upload big size files run below commands from terminal.
```shell
    sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = 20M/" /etc/php/8.2/fpm/php.ini # Default: 2MB
    sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = 20M/" /etc/php/8.2/cli/php.ini # Default: 2MB
    sudo sed -i "s/post_max_size = .*/post_max_size = 30M/" /etc/php/8.2/fpm/php.ini # Default: 8MB This value must be larger than upload_max_filesize and lower than memory_limit
    sudo sed -i "s/post_max_size = .*/post_max_size = 30M/" /etc/php/8.2/cli/php.ini # Default: 8MB This value must be larger than upload_max_filesize and lower than memory_limit
    sudo sed -i -e 's/memory_limit = .*/memory_limit = 4096M/g' /etc/php/8.2/cli/php.ini # Default: 128MB
    sudo sed -i -e 's/memory_limit = .*/memory_limit = 4096M/g' /etc/php/8.2/fpm/php.ini # Default: 128MB
```

- If you modify the `upload_max_filesize` or `post_max_size` settings in php.ini in the future, remember to adjust the `MAX_UPLOAD_SIZE` in the .env files accordingly. This ensures the application uses the correct size limits for file uploads

- If your any of the request will take longer time to execute operation run below commands to increase the execution time.
```shell
    sudo sed -i "s/max_execution_time = .*/max_execution_time = 1200/" /etc/php/8.2/fpm/php.ini # Default: 30 seconds
    sudo sed -i "s/max_execution_time = .*/max_execution_time = 1200/" /etc/php/8.2/cli/php.ini # Default: 30 seconds

    sudo sed -i "s/request_terminate_timeout = .*/request_terminate_timeout = 1200/" /etc/php/8.2/fpm/pool.d/www.conf # Default: Off (0)

    test -f /etc/nginx/conf.d/timeout.conf && sudo sed -i "s/fastcgi_read_timeout .*/fastcgi_read_timeout 1200;/" /etc/nginx/conf.d/timeout.conf || (sudo touch /etc/nginx/conf.d/timeout.conf && echo 'fastcgi_read_timeout 1200;' | sudo tee --append /etc/nginx/conf.d/timeout.conf)
```
- We need to increase the `client_max_body_size` of nginx to allow file uploads. Please add the following line in the the `/etc/nginx/nginx.conf` file's http directive:
    `client_max_body_size 50M;`

- To ensure that all SQL queries do not exceed a maximum execution time of 20 minutes (12,00,000 milliseconds), you need to execute the following command in your MySQL instance:.
    ```
        SET GLOBAL max_execution_time = 1200000;
    ```

- Timezone changed to MYT:
    - Server level - `sudo timedatectl set-timezone Asia/Kuala_Lumpur` (Command to check current timezone - `timedatectl`)
    - Laravel - application timezone is set to `Asia/Kuala_Lumpur` in the config file.
    - MySQL and PHP timezone depends on the server timezone. Just reload PHP FPM & MySQL services.

- [Prevent main branch direct pushes](#prevent-main-branch-direct-pushes)

- Setup [Scheduler](https://laravel.com/docs/9.x/scheduling#main-content) to run every minute. We are generating the member's birthday vouchers every day via cronjob.

- Seed data using **one** of the following:
    - For local, run `php artisan db:seed --class=StaticDataSeeder` to seed static data required to run the system.
    - DB Seed: `php artisan db:seed` (Run if you need auto-generated sample data)
    - For seeding data for mobile POS API, run `php artisan db:seed --class=MobileApiSeeder` to seed 10k records.

- A record for Super admin need to be added manually to the database table `super_admins`.
    - For development: run `php artisan db:seed`. username: `super_admin`, password: `123456`

### Automated Tests

All the backend code has to be covered with automated tests. We are using [Pest](https://pestphp.com) to run automated PHP tests.

Run by `./vendor/bin/pest`

IMP: We are following domain driven coding approach for easier management of various modules and faster tests.

### Continuous Integration (CI) processes

[GitHub Actions](https://github.com/features/actions) are used to automate processes:
- [Pest](https://pestphp.com) for test runs
- [Rector](https://github.com/rectorphp/rector) for automated refactoring
- [ECS](https://github.com/symplify/easy-coding-standard) for automated coding standard fixes
- [Larastan](https://github.com/nunomaduro/larastan) for static analysis checks
- [ESLint](https://eslint.org/) for Javascript code quality checks


### Frontend assets setup

// TODO
- How vite is used to manage assets for multiple panels
- How we remove extra css using purgecss

### Prevent main branch direct pushes

1. Open terminal (not inside VS Code) and cd into the project directory

2. `touch .git/hooks/pre-push` (to create the hook file)

3. `nano .git/hooks/pre-push` (to edit the hook file)

4. Paste the following content in it and save:

```sh
#!/bin/bash

protected_branch='main'
current_branch=$(git symbolic-ref HEAD | sed -e 's,.*/\(.*\),\1,')

if [ $protected_branch = $current_branch ]
then
    echo "${protected_branch} is a protected branch, create PR to merge"
    exit 1 # push will not execute
else
    exit 0 # push will execute
fi
```
5. `chmod +x .git/hooks/pre-push` (to make the hook file executable)

Ref - https://hiltonmeyer.com/articles/protect-git-branch-and-prevent-master-push.html

### Prevent save commit in the main branch

1. Open terminal (not inside VS Code) and cd into the project directory

2. `touch .git/hooks/pre-commit` (to create the hook file)

3. `nano .git/hooks/pre-commit` (to edit the hook file)

4. Paste the following content in it and save:

```sh
#!/bin/bash

protected_branch='main'
current_branch=$(git symbolic-ref HEAD | sed -e 's,.*/\(.*\),\1,')

if [ "$protected_branch" = "$current_branch" ]; then
    echo "Error: You are on the protected branch '$protected_branch'."
    echo "Please create a new branch to commit your changes."
    exit 1 # Stop the commit
fi

exit 0 # Allow the commit on other branches
```
5. `chmod +x .git/hooks/pre-commit` (to make the hook file executable)
