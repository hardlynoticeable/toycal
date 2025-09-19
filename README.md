# Toy Cal

A simple, proof-of-concept Calendar and Contact MCP (Model Context Protocol) server written in PHP.

This project provides a set of backend tools that can be used by an AI assistant to manage a simple calendar and contact list.

## Features

- **Contacts Management**: Full CRUD (Create, Read, Update, Delete) functionality for contacts.
- **Events Management**: Full CRUD functionality for calendar events.
- **Contact/Event Linking**: Associate contacts with events.
- **Date-Range Searching**: Find events that overlap with a given time window.
- **HTTP-based**: Runs on a standard PHP web server (Apache, Nginx, etc.).
- **Test Suite**: Comes with a full PHPUnit test suite for all functionality.

## Technology Stack

- PHP 8.2+
- [php-mcp/server](https://github.com/php-mcp/server)
- Composer for package management
- PHPUnit for automated testing

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP**: Version 8.2 or higher. You will also need the `pdo_sqlite` extension for local development/testing and `pdo_mysql` for production.
- **Composer**: The PHP package manager. For installation instructions, visit [getcomposer.org](https://getcomposer.org/download/).
- **SQLite**: The `sqlite3` command-line interface is required to set up the local database. 
    - On Debian/Ubuntu, you can install it with: `sudo apt install sqlite3`
    - For other operating systems, search for "install sqlite3 on [your OS]".

## Setup and Installation

1.  **Clone the Repository**
    ```bash
    git clone <your-repository-url>
    cd project-directory
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Set up Configuration**

    Copy the example configuration file:
    ```bash
    cp src/config.php.example src/config.php
    ```
    Now, edit `src/config.php` to add your database credentials. By default, it is configured to use a local SQLite database.

4.  **Set up Database**

    For a local SQLite setup, create the database file and schema:
    ```bash
    sqlite3 database.sqlite < schema.sql
    ```
    For a production MySQL setup, import `schema.sql` into your MySQL database.

5.  **Run the Local Server**
    ```bash
    php -S localhost:8000
    ```
    Your MCP server will be available at `http://localhost:8000/mcp-server.php`.

## Running Tests

To run the automated test suite, execute the following command from the project root:

```bash
./vendor/bin/phpunit
```
