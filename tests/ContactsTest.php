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

        // 2. Test with multiple contacts and default order (name ASC)
        $this->contacts->create('Charlie', 'charlie@example.com');
        $this->contacts->create('Alice', 'alice@example.com');
        $this->contacts->create('Bob', 'bob@example.com');

        $result = $this->contacts->list();
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(3, $decoded);
        $this->assertEquals('Alice', $decoded[0]['name']);
        $this->assertEquals('Bob', $decoded[1]['name']);
        $this->assertEquals('Charlie', $decoded[2]['name']);

        // 3. Test ordering by email DESC
        $result = $this->contacts->list('email', 'DESC');
        $decoded = json_decode($result, true);
        $this->assertEquals('charlie@example.com', $decoded[0]['email']);
        $this->assertEquals('bob@example.com', $decoded[1]['email']);
        $this->assertEquals('alice@example.com', $decoded[2]['email']);
    }

    public function testFindContact(): void
    {
        // 1. Test with no matching contacts
        $result = $this->contacts->find('email', 'nobody@example.com');
        $this->assertEquals('No contacts found matching that term.', $result);

        // 2. Test finding by a specific field (email)
        $this->contacts->create('Carol', 'carol@example.com', '555-0123');
        $this->contacts->create('David', 'david@example.com');

        $result = $this->contacts->find('email', 'carol@example.com');
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Carol', $decoded[0]['name']);

        // 3. Test finding by id
        $this->contacts->create('Eve', 'eve@example.com');
        $id = $this->pdo->lastInsertId();
        $result = $this->contacts->find('id', (string)$id);
        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Eve', $decoded[0]['name']);
    }

    public function testFindContactByNameFlexible(): void
    {
        $this->contacts->create('Stephen J. Akins', 'stephen@example.com');
        $this->contacts->create('Steven Smith', 'steven@example.com');

        // Find by first name
        $result = $this->contacts->find('name', 'Stephen');
        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Stephen J. Akins', $decoded[0]['name']);

        // Find by last name
        $result = $this->contacts->find('name', 'Akins');
        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);

        // Find by first and last name
        $result = $this->contacts->find('name', 'Stephen Akins');
        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);

        // Find by initial and last name
        $result = $this->contacts->find('name', 'J. Akins');
        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);

        // Find with multiple matches
        $result = $this->contacts->find('name', 'Ste');
        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded);
    }

    public function testFindContactWithInvalidField(): void
    {
        $result = $this->contacts->find('zodiac_sign', 'Leo');
        $this->assertEquals('Error: Invalid search field specified. Allowed fields are: id, name, email, phone.', $result);
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
