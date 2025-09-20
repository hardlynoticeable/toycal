<?php

namespace App;

use PhpMcp\Server\Attributes\McpTool;
use PDO;
use Throwable;

class Events
{
    /**
     * Creates a new event and links it to contacts.
     *
     * @param string $heading The heading or title of the event.
     * @param int $startTime The UNIX timestamp for the start of the event.
     * @param int $endTime The UNIX timestamp for the end of the event.
     * @param string|null $description A description for the event.
     * @param int[]|null $contactIds An array of contact IDs to associate with this event.
     * @return string A confirmation message.
     */
    #[McpTool(name: 'events-create', description: "Creates a new event and optionally links it to a list of contact IDs.")]
    public function create(string $heading, int $startTime, int $endTime, ?string $description = null, ?array $contactIds = null): string
    {
        if (empty(trim($heading))) {
            return "Error: Event heading cannot be empty.";
        }

        if ($endTime < $startTime) {
            return "Error: End time cannot be before start time.";
        }

        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // 1. Create the event
            $eventSql = "INSERT INTO events (heading, description, start_time, end_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($eventSql);
            $timestamp = time();
            $stmt->execute([$heading, $description, $startTime, $endTime, $timestamp, $timestamp]);
            $eventId = $pdo->lastInsertId();

            // 2. Link contacts, if any
            if (!empty($contactIds)) {
                $linkSql = "INSERT INTO event_contacts (event_id, contact_id) VALUES (?, ?)";
                $stmt = $pdo->prepare($linkSql);
                foreach ($contactIds as $contactId) {
                    $stmt->execute([$eventId, $contactId]);
                }
            }

            $pdo->commit();

            return "Successfully created event with ID {$eventId}.";

        } catch (Throwable $e) {
            $pdo->rollBack();
            // In a real app, you would log the detailed error from $e->getMessage()
            return "Error: Could not create event due to a database error.";
        }
    }

    /**
     * Lists all events.
     *
     * @return string A JSON string representing a list of events.
     */
    #[McpTool(name: 'events-list', description: "Lists all events, ordered by start time.  It is recommended to use the events-find tool instead as you can limit the number of results.")]
    public function list(): string
    {
        $sql = "SELECT id, heading, description, start_time, end_time FROM events ORDER BY start_time ASC";
        
        $pdo = Database::getConnection();
        $stmt = $pdo->query($sql);
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($events)) {
            return "No events found.";
        }
        
        return json_encode($events);
    }

    /**
     * Finds events that overlap with a given time range.
     *
     * @param int $startTime The UNIX timestamp for the start of the search window.
     * @param int $endTime The UNIX timestamp for the end of the search window.
     * @return string A JSON string of matching events.
     */
    #[McpTool(name: 'events-find', description: "Finds events that overlap with a given time range.")]
    public function find(int $startTime, int $endTime): string
    {
        // Find events where the event's time span overlaps with the query's time span.
        // Logic: (EventStart < QueryEnd) AND (EventEnd > QueryStart)
        $sql = "SELECT id, heading, description, start_time, end_time FROM events WHERE start_time < ? AND end_time > ? ORDER BY start_time ASC";
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$endTime, $startTime]);
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($events)) {
            return "No events found in that time range.";
        }
        
        return json_encode($events);
    }

    /**
     * Updates an existing event's details.
     *
     * @param int $id The ID of the event to update.
     * @param string|null $heading The new heading.
     * @param int|null $startTime The new start time.
     * @param int|null $endTime The new end time.
     * @param string|null $description The new description.
     * @return string A confirmation message.
     */
    #[McpTool(name: 'events-update', description: "Updates an existing event's details.")]
    public function update(int $id, ?string $heading = null, ?int $startTime = null, ?int $endTime = null, ?string $description = null): string
    {
        $fields = [];
        $params = [];

        if ($heading !== null) {
            $fields[] = 'heading = ?';
            $params[] = $heading;
        }
        if ($startTime !== null) {
            $fields[] = 'start_time = ?';
            $params[] = $startTime;
        }
        if ($endTime !== null) {
            $fields[] = 'end_time = ?';
            $params[] = $endTime;
        }
        if ($description !== null) {
            $fields[] = 'description = ?';
            $params[] = $description;
        }

        if (empty($fields)) {
            return "Error: No fields provided to update.";
        }

        $fields[] = 'updated_at = ?';
        $params[] = time();

        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            return "Successfully updated event ID {$id}.";
        } else {
            return "Error: Event with ID {$id} not found or no changes made.";
        }
    }

    /**
     * Deletes an event and its contact associations.
     *
     * @param int $id The ID of the event to delete.
     * @return string A confirmation message.
     */
    #[McpTool(name: 'events-delete', description: "Deletes an event and its associations.")]
    public function delete(int $id): string
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            // Delete associations first
            $linkSql = "DELETE FROM event_contacts WHERE event_id = ?";
            $pdo->prepare($linkSql)->execute([$id]);

            // Delete the event itself
            $eventSql = "DELETE FROM events WHERE id = ?";
            $stmt = $pdo->prepare($eventSql);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                return "Successfully deleted event ID {$id}.";
            } else {
                $pdo->rollBack();
                return "Error: Event with ID {$id} not found.";
            }
        } catch (Throwable $e) {
            $pdo->rollBack();
            return "Error: Could not delete event due to a database error.";
        }
    }
}
