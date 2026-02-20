1. Add `127.0.0.1 symfony2.local` to your `/etc/hosts` file
2. `cp .env.dev .env` to copy the development environment variables
3. Run `docker compose up --wait` to set up and start a fresh Symfony project
4. `docker compose exec php bash` to open a shell in the container
5. `composer install` to install the project dependencies
6. `php bin/console doctrine:migrations:migrate` to run the database migrations
7. Open `https://symfony2.local:4413` in your favorite web browser and
