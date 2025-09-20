# Toy Cal

A simple, MCP (Model Context Protocol) Calendar Server written in PHP.  You can run it locally or on nearly any hosting service.  

This project provides a set of backend tools that can be used by an AI assistant to manage a simple calendar and contact list.

**There is virtually no security.  It is not recommended for production use.**

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

## Usage

You can interact with the server by sending MCP-compliant JSON-RPC requests to the `mcp-server.php` endpoint.

Here is an example of a `tools/list` request using `curl`:

```bash
curl -X POST http://localhost:8000/mcp-server.php \
     -H "Content-Type: application/json" \
     -d '{ "jsonrpc": "2.0", "method": "tools/list", "id": "1" }'
```

And an example of a `tools/call` request to create a new contact:

```bash
curl -X POST http://localhost:8000/mcp-server.php \
     -H "Content-Type: application/json" \
     -d '{ "jsonrpc": "2.0", "method": "tools/call", "id": "2", "params": { "name": "John Doe", "email": "john.doe@example.com" } }'
```

## Editing Tool Definitions

Tools are defined as public methods in the `src/Contacts.php` and `src/Events.php` files. To be discovered by the server, these methods must be marked with the `#[McpTool]` attribute.

The `McpTool` attribute has two important parameters:

- `name`: This is the official name of the tool as it will be exposed by the server. It must be a unique string containing only alphanumeric characters, hyphens, and underscores (e.g., `contacts-create`).
- `description`: This is a natural language description of what the tool does. It should be clear and concise to help an AI agent understand the tool's purpose.

**Example:**
```php
/**
 * Creates a new contact.
 *
 * @param string $name The name of the contact.
 * @param string|null $email The email address of the contact.
 * @param string|null $phone The phone number of the contact.
 * @param string|null $notes Any notes about the contact.
 * @return string A confirmation message.
 */
#[McpTool(name: 'contacts-create', description: "Creates a new contact.")]
public function create(string $name, ?string $email = null, ?string $phone = null, ?string $notes = null): string
{
    // ...
}
```

When you edit a tool's name or description, remember to run the test suite (`./vendor/bin/phpunit`) to ensure that your changes have not introduced any regressions. The `tests/ServerTest.php` file specifically tests that all tools are discovered correctly and have non-empty descriptions.

## Contributing

Contributions are welcome! Please feel free to submit a pull request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Project Contributors

This project was developed in a unique collaboration between a human director and an AI agent.

- **Human Director**: Stephen
- **AI Agent**: Gemini (A large language model by Google)

For more information on the development process and conventions for future AI agents, please see [AGENTS.md](AGENTS.md).
