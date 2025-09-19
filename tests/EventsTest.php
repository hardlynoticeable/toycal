<?php

namespace Tests;

use App\Contacts;
use App\Events;
use PDO;
use PHPUnit\Framework\TestCase;

class EventsTest extends TestCase
{
    private Events $events;
    private Contacts $contacts;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = new Events();
        $this->contacts = new Contacts(); // We'll need this to create contacts to link

        $this->pdo = $GLOBALS['test_pdo'];

        // Clear all tables before each test
        $this->pdo->exec('DELETE FROM event_contacts');
        $this->pdo->exec('DELETE FROM events');
        $this->pdo->exec('DELETE FROM contacts');
    }

    public function testCreateEventWithContacts(): void
    {
        // 1. Create some contacts to link to
        $this->contacts->create('Test Contact 1');
        $contactId1 = $this->pdo->lastInsertId();
        $this->contacts->create('Test Contact 2');
        $contactId2 = $this->pdo->lastInsertId();

        // 2. Create the event and link it to the contacts
        $startTime = time();
        $endTime = $startTime + 3600; // 1 hour later
        $result = $this->events->create(
            'Team Meeting',
            $startTime,
            $endTime,
            'Discuss project status',
            [$contactId1, $contactId2]
        );

        $this->assertStringContainsString('Successfully created event', $result);
        
        // Extract the event ID from the result string
        preg_match('/ID (\d+)/', $result, $matches);
        $eventId = $matches[1];

        // 3. Verify the event was created
        $stmt = $this->pdo->query("SELECT * FROM events WHERE id = {$eventId}");
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Team Meeting', $event['heading']);

        // 4. Verify the contacts were linked
        $stmt = $this->pdo->query("SELECT * FROM event_contacts WHERE event_id = {$eventId}");
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $links);
        $this->assertEquals($contactId1, $links[0]['contact_id']);
        $this->assertEquals($contactId2, $links[1]['contact_id']);
    }

    public function testCreateEventFailsWithInvalidTime(): void
    {
        $startTime = time();
        $endTime = $startTime - 3600; // 1 hour BEFORE

        $result = $this->events->create('Time Travel Meeting', $startTime, $endTime);

        $this->assertEquals('Error: End time cannot be before start time.', $result);

        // Verify that no event was created
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM events");
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }

    public function testListEvents(): void
    {
        // 1. Test with no events
        $result = $this->events->list();
        $this->assertEquals('No events found.', $result);

        // 2. Test with multiple events and check order
        $now = time();
        $this->events->create('Event Later', $now + 3600, $now + 7200);
        $this->events->create('Event Earlier', $now, $now + 3600);

        $result = $this->events->list();
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        // Verify they are ordered by start_time ASC
        $this->assertEquals('Event Earlier', $decoded[0]['heading']);
        $this->assertEquals('Event Later', $decoded[1]['heading']);
    }

    public function testFindEventsByDateRange(): void
    {
        $baseTime = strtotime('2025-09-18 12:00:00');

        // Event 1: 10:00 - 11:00
        $this->events->create('Event 1', $baseTime - 7200, $baseTime - 3600);
        // Event 2: 11:30 - 12:30 (Overlaps start of search)
        $this->events->create('Event 2', $baseTime - 1800, $baseTime + 1800);
        // Event 3: 13:00 - 14:00 (Fully inside search)
        $this->events->create('Event 3', $baseTime + 3600, $baseTime + 7200);
        // Event 4: 14:30 - 15:30 (Overlaps end of search)
        $this->events->create('Event 4', $baseTime + 9000, $baseTime + 12600);
        // Event 5: 16:00 - 17:00
        $this->events->create('Event 5', $baseTime + 14400, $baseTime + 18000);

        // Search window: 12:00 - 15:00
        $searchStart = $baseTime;
        $searchEnd = $baseTime + 10800;

        $result = $this->events->find($searchStart, $searchEnd);
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(3, $decoded, 'Should find events 2, 3, and 4');
        $this->assertEquals('Event 2', $decoded[0]['heading']);
        $this->assertEquals('Event 3', $decoded[1]['heading']);
        $this->assertEquals('Event 4', $decoded[2]['heading']);

        // Test no results
        $result = $this->events->find($baseTime - 10000, $baseTime - 9000);
        $this->assertEquals('No events found in that time range.', $result);
    }

    public function testUpdateEvent(): void
    {
        $now = time();
        $this->events->create('Original Event', $now, $now + 3600);
        $result = $this->events->list();
        $eventId = json_decode($result, true)[0]['id'];

        $newStartTime = $now + 100;
        $result = $this->events->update($eventId, heading: 'Updated Event', startTime: $newStartTime);
        $this->assertEquals("Successfully updated event ID {$eventId}.", $result);

        // Verify the data was changed
        $stmt = $this->pdo->query("SELECT * FROM events WHERE id = {$eventId}");
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Updated Event', $event['heading']);
        $this->assertEquals($newStartTime, $event['start_time']);
    }

    public function testUpdateNonExistentEvent(): void
    {
        $result = $this->events->update(999, heading: 'Ghost Event');
        $this->assertEquals("Error: Event with ID 999 not found or no changes made.", $result);
    }

    public function testDeleteEvent(): void
    {
        // Create an event with a linked contact
        $this->contacts->create('Contact To Link');
        $contactId = $this->pdo->lastInsertId();
        $result = $this->events->create('Event To Delete', time(), time() + 3600, null, [$contactId]);
        preg_match('/ID (\d+)/', $result, $matches);
        $eventId = $matches[1];

        // Delete the event
        $deleteResult = $this->events->delete($eventId);
        $this->assertEquals("Successfully deleted event ID {$eventId}.", $deleteResult);

        // Verify the event is gone
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM events WHERE id = {$eventId}");
        $this->assertEquals(0, $stmt->fetchColumn());

        // Verify the link is gone
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM event_contacts WHERE event_id = {$eventId}");
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testDeleteNonExistentEvent(): void
    {
        $result = $this->events->delete(999);
        $this->assertEquals("Error: Event with ID 999 not found.", $result);
    }
}
