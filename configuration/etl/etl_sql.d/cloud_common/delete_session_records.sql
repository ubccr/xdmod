DELETE FROM modw_cloud.session_records WHERE instance_id IN (SELECT DISTINCT instance_id FROM modw_cloud.event_reconstructed)
//
