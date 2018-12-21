USE modw_cloud;

-- When adding new OpenStack events to track we are also updating the mapping of
-- somme OpenStack events. The events that already exist in the staging and event
-- need to have their mappings updated. Some of the updates include mapping the
-- compute.instance.power_on.start event to POWER_ON_START event and
-- compute.instance.resume.start to REQUEST_RESUME and compute.instance.resume.end 
-- to RESUME.

UPDATE `openstack_staging_event` SET event_type_id = 58 WHERE event_type_id = 7;
UPDATE `openstack_staging_event` SET event_type_id = 45 WHERE event_type_id IN (8,17);

UPDATE `event` SET event_type_id = 58 WHERE event_type_id = 7;
UPDATE `event` SET event_type_id = 45 WHERE event_type_id IN (8,17);
