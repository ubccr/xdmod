-- Since we are adding a new 'last_modified' column to event tables, we need to set the 
-- last modified date to the current time so that a one-time reingest + aggregate can be performed.

UPDATE `event` SET last_modified = now() WHERE last_modified = '0000-00-00 00:00:00';
UPDATE `event_reconstructed` SET last_modified = now() WHERE last_modified = '0000-00-00 00:00:00';
UPDATE `cloud_events_transient` SET last_modified = now() WHERE last_modified = '0000-00-00 00:00:00';
