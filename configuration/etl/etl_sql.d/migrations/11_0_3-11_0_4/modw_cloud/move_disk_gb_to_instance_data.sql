-- Populate instance_data.disk_gb from the corresponding instance_type.disk_gb
-- via the instance_type_id foreign key. This must run after disk_gb has been
-- added to instance_data and before it is dropped from instance_type.
UPDATE modw_cloud.instance_data itd
JOIN modw_cloud.instance_type itt ON itd.instance_type_id = itt.instance_type_id
SET itd.disk_gb = itt.disk_gb//
