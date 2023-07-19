# api.holofair.io

## How to run

1. Clone the repository
    ```
    git clone https://github.com/Outreal-XR/api.holofair.io.git
    ```
2. Install dependencies (php)
    ```
    composer install
    ```
3. Configure .env file
    1. Copy .env.example file
    2. Rename the copy to .env
    3. Generate application key
        ```
        php artisan key:generate
        ```
    4. Create database
    5. Configure database credentials in .env file
4. Migration
    ```
    php artisan migrate
    ```
5. Run the server
    ```
    php artisan serve
    ```
