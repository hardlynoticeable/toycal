<?php

namespace Tests;

use App\Contacts;
use PDO;
use PHPUnit\Framework\TestCase;

class ContactsTest extends TestCase
{
    private Contacts $contacts;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contacts = new Contacts();

        // Get the PDO connection from our bootstrap file
        $this->pdo = $GLOBALS['test_pdo'];

        // Clear the contacts table before each test
        $this->pdo->exec('DELETE FROM contacts');
    }

    public function testCreateContact(): void
    {
        $result = $this->contacts->create('John Doe', 'john.doe@example.com', '123-456-7890');

        $this->assertStringContainsString('Successfully created contact', $result);

        // Now, let's verify the data was actually inserted into the database.
        $stmt = $this->pdo->query("SELECT * FROM contacts WHERE email = 'john.doe@example.com'");
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($contact);
        $this->assertEquals('John Doe', $contact['name']);
    }

    public function testCreateContactFailsWithEmptyName(): void
    {
        $result = $this->contacts->create(' '); // Empty name

        $this->assertEquals('Error: Contact name cannot be empty.', $result);

        // Verify that no contact was inserted
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM contacts");
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }

    public function testListContacts(): void
    {
        // 1. Test with no contacts
        $result = $this->contacts->list();
        $this->assertEquals('No contacts found.', $result);

        // 2. Test with multiple contacts
        $this->contacts->create('Alice', 'alice@example.com');
        $this->contacts->create('Bob', 'bob@example.com');

        $result = $this->contacts->list();
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals('Alice', $decoded[0]['name']);
        $this->assertEquals('Bob', $decoded[1]['name']);
    }

    public function testFindContact(): void
    {
        // 1. Test with no matching contacts
        $result = $this->contacts->find('nobody');
        $this->assertEquals('No contacts found matching that term.', $result);

        // 2. Test finding a specific contact
        $this->contacts->create('Carol', 'carol@example.com', '555-0123');
        $this->contacts->create('David', 'david@example.com');

        // Find by email
        $result = $this->contacts->find('carol@example.com');
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Carol', $decoded[0]['name']);
    }

    public function testUpdateContact(): void
    {
        $this->contacts->create('Original Name', 'original@example.com');
        $id = $this->pdo->lastInsertId();

        $result = $this->contacts->update($id, name: 'Updated Name', phone: '555-9999');
        $this->assertEquals("Successfully updated contact ID {$id}.", $result);

        // Verify the data was changed
        $stmt = $this->pdo->query("SELECT * FROM contacts WHERE id = {$id}");
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Updated Name', $contact['name']);
        $this->assertEquals('555-9999', $contact['phone']);
        $this->assertEquals('original@example.com', $contact['email']); // Ensure other fields are untouched
    }

    public function testUpdateNonExistentContact(): void
    {
        $result = $this->contacts->update(999, name: 'Ghost');
        $this->assertEquals("Error: Contact with ID 999 not found or no changes made.", $result);
    }

    public function testDeleteContact(): void
    {
        $this->contacts->create('To Be Deleted', 'delete@example.com');
        $id = $this->pdo->lastInsertId();

        $result = $this->contacts->delete($id);
        $this->assertEquals("Successfully deleted contact ID {$id}.", $result);

        // Verify the contact is gone
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM contacts WHERE id = {$id}");
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }

    public function testDeleteNonExistentContact(): void
    {
        $result = $this->contacts->delete(999);
        $this->assertEquals("Error: Contact with ID 999 not found.", $result);
    }
}
