## Prerequisites
- A full working installation of XDMoD. [XDMoD install instructions](install.html)

## What are cloud metrics?
The Cloud realm in Open XDMoD tracks events that occur in cloud infrastructure systems which can also be referred to as Infrastructure as a Service (IaaS) cloud computing systems. A variety of events are tracked such as starting or ending sessions of a VM or the amount of root volume storage used by running sessions. The characteristics of cloud instances differ in several ways from traditional HPC resources, hence the metrics that we track for cloud systems differ from the metrics we track for traditional HPC jobs.

## Available metrics
- Average Memory Reserved Weighted By Wall Hours (Bytes)
  - The average amount of memory (in bytes) reserved by VM's, weighted by wall hours.
- Average Root Volume Storage Reserved Weighed By Wall Hours (Bytes)
  - The average amount of root volume disk space (in bytes) reserved by VM's, weighted by wall hours.
- Average Wall Hours per Session
  - The average wall time that a session was running, in hours.
- Core Hour Utilization: %
  - A percentage that shows how many core hours were allocated to VM's that were running over a time period against how many core hours a resource had available during that time period.
- Core Hours: Total
  - The total number of core hours consumed by VM's.
- Number of Sessions Ended
  - The  total  number  of  sessions  that  were  ended  on  a  cloud  resource.  A  session  is  ended  when  a  VM  is  paused,  shelved,  stopped,  or  terminated  on  a  cloud  resource.
- Number of Active Sessions
  - The  total  number  of  sessions  on  a  cloud  resource.
- Number of Sessions Started
  - The  total  number  of  sessions  started  on  a  cloud  resource.  A  session  begins  when  a  VM  is  created,  unshelved,  or  resumes  running  on  a  cloud  resource.
- Wall Hours: Total
  - The total wall time in which VM's were running, in hours.

## Dimensions available for grouping
- Instance Type
  - The instance type of the virtual machines.
- Project
  - The  project  associated  with a virtual  machine.
- PI
  - The principal investigator of a project has a valid allocation, which can be used by the PI or the members of their project to run VM's on.
- Resource
  - A  resource  is  defined  as  any remote  infrastructure  that  hosts  cloud  instances.
- User
  - A person on a principal investigator's allocation, able to spin up and manipulate VM instances.
- System Username
  - The specific system username associated with a running session of a virtual machine.
- VM Size: Cores
  - A categorization of VM's into discrete groups based on the number of cores used by each VM.
- VM Size: Memory
  - A categorization of VM's into discrete groups based on the amount of memory reserved by each VM.
- VM State
  - A categorization of VM's based on their state, ex. Active, Inactve, etc.
- Domain
  - A domain is a high-level container for projects, users and groups in Open Stack.
- Submission Venue
  - The venue that a job or cloud instance was initiated from.

## Getting cloud metrics data
XDMoD provides the ability to read data from a predefined infrastructure-agnostic file format containing cloud system events. The schema for this file can be found in `configuration/etl/etl_schemas.d/cloud/event.schema.json`. For OpenStack based systems we also support data ingest using the OpenStack API and a [patch](https://github.com/ubccr/openstack-api-reporting-patch) to create cloud event logs for direct ingestion into XDMoD.

## Events needed
There are a set of events that are necessary to be included in the cloud log files in order for the cloud metrics to display accurate data. Below is a list of a few examples of these events while the full list of events that XDMoD tracks can be found in the file `configuration/etl/etl_data.d/cloud_common/event_type.json`

- Starting and stopping an instance.
- Starting and stopping a storage volume.
- Attaching or detaching a storage volume.
- Instance heartbeat - This is an event that reports if an instance is still active. This event is usually reported once an hour and is required for accurate displaying of cloud metrics.

## Details of OpenStack file format for ingestion
The XDMoD team has released a set of patches and a script that will create a properly formatted JSON file for ingestion by XDMoD. These patches and the script to create a JSON file for ingestion can be found in the [openstack-api-reporting-patch repository](https://github.com/ubccr/openstack-api-reporting-patch).

Once the patch is installed the `openstack_api_reporting.py` file should be run once a day to get event data from the previous day.

## Details of generic file format for ingestion
If you choose to use the generic file format for ingesting event data each event being tracked should create a JSON record with the following attributes. The formatting below is purely for readability. When adding events to your JSON log file there should only be line breaks between each event record, not each attribute in the event record.

```json
{
    "node_controller": "IP address of node controller",
    "public_ip": "Publically available IP address",
    "account": "Account that user is logged into",
    "event_type": "Type of event",
    "event_time": "Time that event happened",
    "instance_type": {
        "name": "Name of VM",
        "cpu": "Number of CPU's the instance has",
        "memory": "Amount of memory the instance has",
        "disk": "Amount of storage space in GB this instance has",
        "networkInterfaces": "Number of network interfaces"
    },
    "image_type": "Name of the type of image this instance uses",
    "instance_id": "ID for the VM instance",
    "record_type": "Type of record from list in modw_cloud.record_type table",
    "block_devices": [{
        "account": "Account that the storage device belongs to",
        "attach_time": "Time that the storage device was attached to this instance",
        "backing": "type of storage used for this block device, either ebs or instance-store",
        "create_time": "Time the storage device was created",
        "user": "User that the storage device was created by",
        "id": "ID of the storage volume",
        "size": "Size in GB of the storage volume"
    }],
    "private_ip": "Private IP address used by the instance",
    "root_type": "Type of storage initial storage volume is, either ebs or instance-store"
}
```

### Special notes
- The instance_type attribute is a JSON Object with details of the instance type for the VM this event occurred on.
- The block_devices attribute is a JSON object that lists information about block storage devices attached to this VM when the event occurred. If multiple storage devices are attached the should each be listed here as a separate JSON object.

## Adding PI information
PI information for the the cloud realm is ingested from a csv file using the `xdmod-import-csv` command. When ingesting the data
the -t flag should be set to cloud-project-to-pi. An example of the command is below:

    xdmod-import-csv -t cloud-project-to-pi -i /path/to/file.csv

After importing this data you must ingest it for the date range of any data you have already shredded.

    xdmod-ingestor --datatype=genericcloud
    xdmod-ingestor --datatype=openstack
    xdmod-ingestor --aggregate=cloud --last-modified-start-date 2012-01-01

### Format
The format of the csv file into set a project to PI association is shown below

```csv
pi,project_name,resource_name
pi2,project_name2,resource_name
```

The first column should be the username of the PI as seen in your resources event log files. The second column is the name of the project
as seen in your resources event log files. The third column is the name of the resource in XDMoD.

If you want the first and last name of the PI to be shown instead of their username when viewing this data you should add the PI username and
first and last name to the `names.csv` file and ingested. Details on doing this can be found in the [`User/PI Names`](user-names.md) documentation.

## Hierarchy

Open XDMoD allows you to define a three level hierarchy that can be used to define various entities or groups and associate users with a group in
the hierarchy. These can be decanal units and their associated departments or any hierarchy that is desired.  If defined, this hierarchy is used
to generate charts that aggregate cloud metrics into groups based on users assigned to one of the groups.

See the [Hierarchy Guide](hierarchy.html) for more details.

## Utilization

To use the Utilization statistic you must shred and ingest a JSON file that lists the specifications of each node in your cloud resource. The name of this file should follow the format `hypervisor_facts_YYYY-MM-DDTHH:SS:MM.json`. When the details of at least one node changes a new file listing the details of all of the nodes should be created and ingested. Below is the format for the JSON file. For resources that use OpenStack we have a python script that will create a file with appropriate information in the [xdmod-openstack-scripts repository](https://github.com/ubccr/xdmod-openstack-scripts/blob/master/hypervisor_fact_reporting). This script should be run once a day and will create a file only on days where the details of at least one node has changed from the previous day. The files should be ingested using `xdmod-shredder` and setting the `-f` option to `cloudresourcespecs` and then running `xdmod-ingestor`. To ingest only this data set the `--datatype` option `cloudresourcespecs`. An example of the commands needed are below.

    xdmod-shredder -r RESOURCE_NAME -d PATH/TO/DIRECTORY -f cloudresourcespecs
    xdmod-ingestor --datatype=cloudresourcespecs

### Cloud Resource Specification JSON file example
```json
{
    "hypervisors": [
        {
            "hypervisor_hostname": "node1.example.com",
            "id": 1,
            "memory_mb": 205583,
            "vcpus": 56
        },
        {
            "hypervisor_hostname": "node2.example.com",
            "id": 2,
            "memory_mb": 209314,
            "vcpus": 56
        },
        {
            "hypervisor_hostname": "node3.example.com",
            "id": 3,
            "memory_mb": 147512,
            "vcpus": 56
        },
        {
            "hypervisor_hostname": "node4.example.com",
            "id": 5,
            "memory_mb": 196714,
            "vcpus": 56
        },
        {
            "hypervisor_hostname": "node5.example.com",
            "id": 6,
            "memory_mb": 146597,
            "vcpus": 56
        }
    ],
    "ts": "2018-04-17T22:30:02Z"
}
```

## Adding and enabling cloud resources

### Add a cloud resource
Cloud resources are added by using the xdmod-setup command.

1.  Type `xdmod-setup`.
2.  Select option 4, `Resources`, on Open XDMoD Setup screen.
3.  Then select option 1, `Add a new resource`.
4.  Enter information for the prompts that follow. When you see the Resource Type prompt, enter `cloud` as the resource type.
5.  Once you finish with all the prompts you will be redirected to the `Resource Setup` screen. At this screen choose the `s` option to save the information you just entered to the resources.json configuration file.
6.  Run the following command from the command line to load data from resources.json file into the database:
`php /usr/share/xdmod/tools/etl/etl_overseer.php -p ingest-resources`

### Ingesting cloud event data
Cloud data is shredded and ingested using the [`xdmod-shredder`](shredder.md) and [`xdmod-ingestor`](ingestor.md) commands. Please see their respective guides for further information.
