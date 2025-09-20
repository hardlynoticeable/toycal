<?php

namespace App;

use PhpMcp\Server\Attributes\McpTool;
use PDO;

class Contacts
{
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
        // Basic validation
        if (empty(trim($name))) {
            return "Error: Contact name cannot be empty.";
        }

        $sql = "INSERT INTO contacts (name, email, phone, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        
        $timestamp = time();
        
        $stmt->execute([
            trim($name),
            $email,
            $phone,
            $notes,
            $timestamp,
            $timestamp
        ]);
        
        $newId = $pdo->lastInsertId();
        
        return "Successfully created contact '{$name}' with ID {$newId}.";
    }

    /**
     * Lists all contacts.
     *
     * @param string $orderBy The field to order by (e.g., 'name', 'email'). Defaults to 'name'.
     * @param string $order The direction to order (ASC or DESC). Defaults to 'ASC'.
     * @return string A JSON string representing a list of contacts.
     */
    #[McpTool(name: 'contacts-list', description: "Lists all contacts, with optional sorting.")]
    public function list(string $orderBy = 'name', string $order = 'ASC'): string
    {
        // Prevent SQL injection by validating orderBy parameter
        $allowedOrderBy = ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
        if (!in_array(strtolower($orderBy), $allowedOrderBy)) {
            $orderBy = 'name';
        }

        // Validate order parameter
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT id, name, email, phone, notes FROM contacts ORDER BY {$orderBy} {$order}";
        
        $pdo = Database::getConnection();
        $stmt = $pdo->query($sql);
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contacts)) {
            return "No contacts found.";
        }
        
        return json_encode($contacts);
    }

    /**
     * Finds contacts by a specific field and value.
     *
     * @param string $field The field to search by. Must be one of: 'id', 'name', 'email', 'phone'.
     * @param string $value The value to search for.
     * @return string A JSON string of matching contacts.
     */
    #[McpTool(name: 'contacts-find', description: "Finds contacts by a specific field (id, name, email, or phone).")]
    public function find(string $field, string $value): string
    {
        $allowedFields = ['id', 'name', 'email', 'phone'];
        if (!in_array(strtolower($field), $allowedFields)) {
            return "Error: Invalid search field specified. Allowed fields are: id, name, email, phone.";
        }

        $pdo = Database::getConnection();
        
        if (strtolower($field) === 'name') {
            // Handle flexible name searching
            $searchTerms = explode(' ', $value);
            $sqlParts = [];
            $params = [];
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $sqlParts[] = "name LIKE ?";
                    $params[] = '%' . $term . '%';
                }
            }
            if (empty($sqlParts)) {
                return "No contacts found matching that term.";
            }
            $sql = "SELECT id, name, email, phone, notes FROM contacts WHERE " . implode(' AND ', $sqlParts);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // Handle exact matches for other fields
            $sql = "SELECT id, name, email, phone, notes FROM contacts WHERE {$field} = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$value]);
        }
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contacts)) {
            return "No contacts found matching that term.";
        }
        
        return json_encode($contacts);
    }

    /**
     * Updates an existing contact.
     *
     * @param int $id The ID of the contact to update.
     * @param string|null $name The new name.
     * @param string|null $email The new email.
     * @param string|null $phone The new phone number.
     * @param string|null $notes The new notes.
     * @return string A confirmation message.
     */
    #[McpTool(name: 'contacts-update', description: "Updates an existing contact's details.")]
    public function update(int $id, ?string $name = null, ?string $email = null, ?string $phone = null, ?string $notes = null): string
    {
        $fields = [];
        $params = [];

        if ($name !== null) {
            $fields[] = 'name = ?';
            $params[] = $name;
        }
        if ($email !== null) {
            $fields[] = 'email = ?';
            $params[] = $email;
        }
        if ($phone !== null) {
            $fields[] = 'phone = ?';
            $params[] = $phone;
        }
        if ($notes !== null) {
            $fields[] = 'notes = ?';
            $params[] = $notes;
        }

        if (empty($fields)) {
            return "Error: No fields provided to update.";
        }

        $fields[] = 'updated_at = ?';
        $params[] = time();

        $sql = "UPDATE contacts SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            return "Successfully updated contact ID {$id}.";
        } else {
            return "Error: Contact with ID {$id} not found or no changes made.";
        }
    }

    /**
     * Deletes a contact.
     *
     * @param int $id The ID of the contact to delete.
     * @return string A confirmation message.
     */
    #[McpTool(name: 'contacts-delete', description: "Deletes a contact.")]
    public function delete(int $id): string
    {
        $sql = "DELETE FROM contacts WHERE id = ?";
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return "Successfully deleted contact ID {$id}.";
        } else {
            return "Error: Contact with ID {$id} not found.";
        }
    }
}
