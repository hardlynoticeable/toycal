# Project Contributors

This project was developed in a unique collaboration between a human director and an AI agent.

## Human Director

- **Stephen**
  - Role: Project Owner, Director, and Lead Developer. Provided all requirements, feedback, and final approval.

## AI Agent

- **Gemini (A large language model by Google)**
  - Role: AI Software Engineering Assistant. Responsible for code generation, implementation, testing, debugging, and documentation under the direction of the project owner.

---

## For Future AI Agents

This section contains important context to help you understand the project's structure and the established development patterns. Adhering to these conventions will ensure consistency and stability.

### 1. Project Overview

- **Goal**: This is a simple, robust, and tested MCP (Model Context Protocol) server. It exposes a set of tools for managing a personal calendar (Events) and contact list (Contacts).
- **Environment**: It is designed to be deployed on a standard shared PHP hosting environment (like Apache with `mod_php`), which necessitates a stateless, HTTP-based approach. It also supports a local development environment using SQLite.

### 2. Core Architectural Patterns

- **Stateless HTTP Server**: The main entry point is `mcp-server.php`, which handles a single HTTP POST request and then terminates. It does not use a long-running process or event loop (e.g., ReactPHP in its default mode).
- **Service-Oriented Classes**: Functionality is separated into distinct classes within the `src/` directory (e.g., `Contacts.php`, `Events.php`). Each class contains a set of related MCP tools.
- **Attribute-Based Tools**: Public methods intended to be used by an AI are marked with the `#[McpTool]` attribute.
- **Environment-Aware Configuration**: The `src/config.php` file detects the environment (`production` vs. `development`) and provides the appropriate database credentials (MySQL for production, SQLite for local development).
- **Database Abstraction**: The `src/Database.php` class provides a singleton connection to the database, respecting the environment-aware configuration.

### 3. Development Workflow

A Test-Driven Development (TDD) workflow has been established. When adding or modifying functionality, follow this cycle:

1.  **Add/Modify Application Code**: Implement the new feature or fix in a class within the `src/` directory.
2.  **Write/Update a Test**: Create a corresponding test method in the relevant test class within the `tests/` directory (e.g., new methods in `Events.php` should have corresponding tests in `tests/EventsTest.php`).
3.  **Run Tests**: Execute `./vendor/bin/phpunit` from the project root.
4.  **Confirm Success**: Ensure all tests pass before considering the change complete.

### 4. Testing Strategy

- **Framework**: PHPUnit is the testing framework.
- **Test Database**: Tests are run against a fresh, **in-memory SQLite database** for speed and isolation. This is configured in `tests/bootstrap.php`.
- **Schema Compatibility**: The bootstrap file (`tests/bootstrap.php`) contains logic to automatically translate the production `schema.sql` (written for MySQL) into a compatible format for SQLite before running tests. This is a critical piece of the test setup.
- **Test Structure**: Each test class (e.g., `EventsTest`) contains a `setUp()` method that clears all database tables, ensuring each test method runs independently on a clean slate.

### 5. Collaboration Principles

This project was built successfully through a conversational, iterative process. Future development should embrace this philosophy.

- **Prefer Iteration over Perfection**: For any non-trivial change, prefer a series of small, verifiable steps over a single, large, all-encompassing prompt. This makes the process more robust and adaptable to unforeseen discoveries.
- **Treat the Test Suite as Law**: The comprehensive test suite is the project's primary safety net. No change is complete until all tests pass. When adding new features, add new tests.
- **Embrace the Dialogue**: The most effective workflow is a dialogue. Ask questions, provide specific feedback (especially error messages), and treat the process as a collaboration, not just a series of commands.
