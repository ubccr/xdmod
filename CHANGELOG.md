# Open XDMoD Change Log

## 2022-03-10 v10.0.0

- Bug Fixes
    - ETL
        - Fix comment removal in ETL SQL execution ([\#1609](https://github.com/ubccr/xdmod/pull/1609))
        - Fix cloud resource specs and storage database tables date formats ([\#1600](https://github.com/ubccr/xdmod/pull/1600))
        - Add SHOW WARNINGS to StructuredFileIngestor ([\#1586](https://github.com/ubccr/xdmod/pull/1586))
        - Fix quarters start timestamp column type ([\#1560](https://github.com/ubccr/xdmod/pull/1560))
        - Change slurm helper default end time ([\#1546](https://github.com/ubccr/xdmod/pull/1546))
        - Fix resource ingestor default resource type ([\#1537](https://github.com/ubccr/xdmod/pull/1537))
        - Fix shredder empty line checking ([\#1525](https://github.com/ubccr/xdmod/pull/1525))
    - General
        - Set minimum username length to 2 ([\#1594](https://github.com/ubccr/xdmod/pull/1594))
        - Change exceptions.log file permissions ([\#1550](https://github.com/ubccr/xdmod/pull/1550))
        - Restore global exception file name and number, and stack trace logging ([\#1549](https://github.com/ubccr/xdmod/pull/1549))
        - Remove PEAR Log dependencies ([\#1543](https://github.com/ubccr/xdmod/pull/1543))
        - Add exportJson function back. ([\#1532](https://github.com/ubccr/xdmod/pull/1532))
- Enhancements
    - Internal Dashboard
        - Refactor admin dashboard user listing query and add indexes to Users and SessionManager tables ([\#1606](https://github.com/ubccr/xdmod/pull/1606))
    - ETL
        - Fix warnings seen when ingesting cloud or storage files ([\#1592](https://github.com/ubccr/xdmod/pull/1592))
        - Performance improvements to ETLv2 action that loads queues for jobs realm ([\#1580](https://github.com/ubccr/xdmod/pull/1580))
        - Improve Slurm TRES GRES/GPU parsing ([\#1544](https://github.com/ubccr/xdmod/pull/1544))
    - General
        - Convert tables in moddb database to the InnoDB table engine ([\#1585](https://github.com/ubccr/xdmod/pull/1585))
        - Convert tables in modw_aggregates to InnoDB and add class to manage aggregate tables ([\#1584](https://github.com/ubccr/xdmod/pull/1584))
        - Add index to mod_logger.log_table for help with better query planning ([\#1582](https://github.com/ubccr/xdmod/pull/1582))
        - InnoDB Performance Improvements for ingestion and aggregation ([\#1579](https://github.com/ubccr/xdmod/pull/1579))
        - Convert tables in mod_logger, mod_hpcdb, and mod_shredder to InnoDB ([\#1576](https://github.com/ubccr/xdmod/pull/1576))
        - Convert tables in modw database to the innodb table engine ([\#1573](https://github.com/ubccr/xdmod/pull/1573))
        - Add support for PHP 7.2 and MariaDB 10.3 ([\#1486](https://github.com/ubccr/xdmod/pull/1486))
    - Cloud
        - Convert modw_cloud tables to InnoDB table engine ([\#1572](https://github.com/ubccr/xdmod/pull/1572))
        - Remove multi column auto-increment key from modw_cloud.cloud_resource_specs ([\#1569](https://github.com/ubccr/xdmod/pull/1569))
    - Job Viewer
        - Enabled text selection / copying in Job Viewer Accounting Tab ([\#1561](https://github.com/ubccr/xdmod/pull/1561))
    - Infrastructure
        - Update query filtering to be based on ACLs ([\#1531](https://github.com/ubccr/xdmod/pull/1531))
- Uncategorized
    - General
        - Change SQL connection reuse strategy ([\#1604](https://github.com/ubccr/xdmod/pull/1604))
        - More robustness improvements for webdriverio-based UI tests ([\#1587](https://github.com/ubccr/xdmod/pull/1587))
        - Handle MySQL server has gone away errors ([\#1566](https://github.com/ubccr/xdmod/pull/1566))
- New Features
    - General
        - Add script to help convert to using innodb-file-per-table mysql setting ([\#1598](https://github.com/ubccr/xdmod/pull/1598))
        - Add quality of service group by with Slurm support ([\#1589](https://github.com/ubccr/xdmod/pull/1589))
        - Updated etl_profile.js to add an extra group_by parameter "show_all_dimension_values". ([\#1588](https://github.com/ubccr/xdmod/pull/1588))
        - Update Metric Explorer Controller to include ability to show all possible dimension values. ([\#1578](https://github.com/ubccr/xdmod/pull/1578))

## 2021-05-21 v9.5.0

- New Features
    - General
        - Add walltime_accuracy metric to job summary data. ([\#1515](https://github.com/ubccr/xdmod/pull/1515))
    - Cloud
        - Add cloud instance viewer ([\#1473](https://github.com/ubccr/xdmod/pull/1473))
    - ETL
        - Add -c/--cluster flag to xdmod-slurm-helper ([\#1401](https://github.com/ubccr/xdmod/pull/1401))
- Enhancements
    - General
        - Add indexes that reduce the ingest time for the database load when using the Federated XDMoD module ([\#1500](https://github.com/ubccr/xdmod/pull/1500))
        - Change raw statistics variable substitution format ([\#1448](https://github.com/ubccr/xdmod/pull/1448))
        - Populate exports with metadata where appropriate ([\#1425](https://github.com/ubccr/xdmod/pull/1425))
        - Populate joblist table for gateway jobs ([\#1411](https://github.com/ubccr/xdmod/pull/1411))
    - ETL
        - Add data endpoint that can parse webserver logs. ([\#1483](https://github.com/ubccr/xdmod/pull/1483))
        - Remove use of Slurm ReqGRES field ([\#1479](https://github.com/ubccr/xdmod/pull/1479))
        - Increase maximum queue name length ([\#1460](https://github.com/ubccr/xdmod/pull/1460))
        - Add support for terabyte suffix in SGE/UGE logs ([\#1457](https://github.com/ubccr/xdmod/pull/1457))
    - Internal Dashboard
        - Add data export logs to administrative dashboard ([\#1451](https://github.com/ubccr/xdmod/pull/1451))
    - ACL
        - Use absolute path when running acl-config ([\#1437](https://github.com/ubccr/xdmod/pull/1437))
    - Metric Explorer
        - Restore node utilization statistics ([\#1431](https://github.com/ubccr/xdmod/pull/1431))
- Security
    - Infrastructure
        - Updating file permissions for XDMoD's bin directory ([\#1510](https://github.com/ubccr/xdmod/pull/1510))
        - Switch to Chromium instead of the (no-longer maintained) PhantomJS to generate chart export images. ([\#1413](https://github.com/ubccr/xdmod/pull/1413), [\#1428](https://github.com/ubccr/xdmod/pull/1428))
        - Remove (no longer maintained) jasper reports and replace with PHPWord/libreoffice  for Report generation. ([\#1341](https://github.com/ubccr/xdmod/pull/1341), [\#1496](https://github.com/ubccr/xdmod/pull/1496), [\#1424](https://github.com/ubccr/xdmod/pull/1424))
    - General
        - Disable SSLv3 by default in the template apache configuration file. ([\#1466](https://github.com/ubccr/xdmod/pull/1466))
        - Verifying User Query Count to address TrustedCI feedback ([\#1430](https://github.com/ubccr/xdmod/pull/1430))
- Bug Fixes
    - Data Warehouse Export
        - Fix SUPReMM data warehouse export regression ([\#1520](https://github.com/ubccr/xdmod/pull/1520))
    - General
        - Change chromium output error detection. ([\#1519](https://github.com/ubccr/xdmod/pull/1519))
        - Ensure "Don't show me this" checkbox is honoured ([\#1508](https://github.com/ubccr/xdmod/pull/1508))
        - Ensure dashboard is updated whenever reports change. ([\#1507](https://github.com/ubccr/xdmod/pull/1507))
        - Add missing attributes when gearing over from Usage to Metric ([\#1506](https://github.com/ubccr/xdmod/pull/1506))
        - Improve input validation for download_report controller ([\#1503](https://github.com/ubccr/xdmod/pull/1503))
        - Fix bug in language definition in main html page. ([\#1492](https://github.com/ubccr/xdmod/pull/1492))
        - Change top-level exception handling in scripts ([\#1488](https://github.com/ubccr/xdmod/pull/1488))
        - Fix mysql command line defaults file option ([\#1487](https://github.com/ubccr/xdmod/pull/1487))
        - Re-index filtered arrays ([\#1481](https://github.com/ubccr/xdmod/pull/1481))
        - Improve robustness of full groupby checking ([\#1469](https://github.com/ubccr/xdmod/pull/1469))
        - Re-index value returned from getRawDataRealms function ([\#1468](https://github.com/ubccr/xdmod/pull/1468))
        - Update version of ini package ([\#1467](https://github.com/ubccr/xdmod/pull/1467))
        - Update SSO to pull the configured SP's IdP metadata directly ([\#1455](https://github.com/ubccr/xdmod/pull/1455))
        - Only show the New User Tour Dialog once per session. ([\#1454](https://github.com/ubccr/xdmod/pull/1454))
        - Fix login dialog sign in button rendering on Google Chrome 85+ ([\#1450](https://github.com/ubccr/xdmod/pull/1450))
        - Set the "Show in X tab" label in the Metric Explorer automatically based on whether the dashboard is enabled ([\#1400](https://github.com/ubccr/xdmod/pull/1400))
    - Job Viewer
        - Job Analytics panel charts dynamically configurable. ([\#1517](https://github.com/ubccr/xdmod/pull/1517))
        - Update and add GroupBy categories ([\#1441](https://github.com/ubccr/xdmod/pull/1441))
    - Cloud
        - Updating pipelines that get run when ingesting data in generic cloud data format ([\#1514](https://github.com/ubccr/xdmod/pull/1514))
        - Changing constraints on cloud resource specifications tables to prevent extra rows ([\#1511](https://github.com/ubccr/xdmod/pull/1511))
        - Fix extraneous cloud sessions ([\#1504](https://github.com/ubccr/xdmod/pull/1504))
        - Fixed memory range typo for cloud memory buckets configuration file. ([\#1502](https://github.com/ubccr/xdmod/pull/1502))
        - Add columns to cloudfact_by_day aggregation ([\#1518](https://github.com/ubccr/xdmod/pull/1518))
        - Break cloud migration into two pipelines ([\#1512](https://github.com/ubccr/xdmod/pull/1512))
        - Move to using custom Ingestor for cloud resource specifications ([\#1489](https://github.com/ubccr/xdmod/pull/1489))
        - Make cloud realm compatible with ONLY_FULL_GROUP_BY ([\#1480](https://github.com/ubccr/xdmod/pull/1480))
    - ETL
        - Fix data encoding in etlv2 pdoIngestor ([\#1495](https://github.com/ubccr/xdmod/pull/1495))
        - Fix hang in nodejs etl on mysql connection failure. ([\#1477](https://github.com/ubccr/xdmod/pull/1477))
        - Improve configurability for the stats defined in the etlv1 schema. ([\#1465](https://github.com/ubccr/xdmod/pull/1465))
    - Infrastructure
        - Updating the logging code to not throw away all class information ([\#1493](https://github.com/ubccr/xdmod/pull/1493))
    - ACL
        - Update moddb.module_version table ([\#1484](https://github.com/ubccr/xdmod/pull/1484))
    - Internal Dashboard
        - Fix institution loading during manual user creation ([\#1446](https://github.com/ubccr/xdmod/pull/1446))
    - Report Generator
        - Hide dashboard reports from Report Generator ([\#1445](https://github.com/ubccr/xdmod/pull/1445))
        - Fix chart layout and timeframe radio button crowding ([\#1435](https://github.com/ubccr/xdmod/pull/1435))
- Documentation
    - General
        - Updated the upgrade guide documentation to include information on re-ingesting cloud data due to memory buckets bug fix. ([\#1516](https://github.com/ubccr/xdmod/pull/1516))
        - Create Gateways realm installation instructions ([\#1509](https://github.com/ubccr/xdmod/pull/1509))
        - Update simple SAML documentation to mention the icon. ([\#1505](https://github.com/ubccr/xdmod/pull/1505))
        - Update listing of XDMoD demos from Autumn 2020 ([\#1482](https://github.com/ubccr/xdmod/pull/1482))
        - Remove remaining deprecated html from About tab pages ([\#1476](https://github.com/ubccr/xdmod/pull/1476))
        - Updated XMS project's authors and team listings ([\#1474](https://github.com/ubccr/xdmod/pull/1474))
        - Update About tab with recent Publications and Presentations details ([\#1470](https://github.com/ubccr/xdmod/pull/1470))
        - Removing references to acl-config's recover mode ([\#1418](https://github.com/ubccr/xdmod/pull/1418))
- Maintenance / Code Quality
    - Infrastructure
        - Removing Unused Code ([\#1462](https://github.com/ubccr/xdmod/pull/1462))
        - Updating ubccr/simplesamlphp-module-authoidcoauth2 to 1.1.0 ([\#1447](https://github.com/ubccr/xdmod/pull/1447))
        - Migrate logging code to use MonoLog rather than PEAR Log ([\#1461](https://github.com/ubccr/xdmod/pull/1461))
        - Update the directory that ui/runtests.sh runs from ([\#1438](https://github.com/ubccr/xdmod/pull/1438))
    - General
        - Use the composer autoloader to load files. ([\#1494](https://github.com/ubccr/xdmod/pull/1494))
        - Fix checkbox CSS syntax ([\#1436](https://github.com/ubccr/xdmod/pull/1436))
        - Remove unnecessary report font code ([\#1434](https://github.com/ubccr/xdmod/pull/1434))
- Data Quality
    - General
        - Improve data validity checks for Open XDMoD account usernames ([\#1379](https://github.com/ubccr/xdmod/pull/1379))

## 2020-08-13 v9.0.0

- Documentation
    - ETL
        - Update ETL table data verification help text ([\#1405](https://github.com/ubccr/xdmod/pull/1405))
    - General
        - Remove old documentation ([\#1404](https://github.com/ubccr/xdmod/pull/1404))
        - Minor documentation changes ([\#1402](https://github.com/ubccr/xdmod/pull/1402))
        - Update node utilization documentation ([\#1397](https://github.com/ubccr/xdmod/pull/1397))
        - Update hierarchy dimensions removal process ([\#1394](https://github.com/ubccr/xdmod/pull/1394))
        - Remove beta labels from documentation ([\#1386](https://github.com/ubccr/xdmod/pull/1386))
        - Update Integration Documentation ([\#1383](https://github.com/ubccr/xdmod/pull/1383))
        - Update hierarchy documentation ([\#1376](https://github.com/ubccr/xdmod/pull/1376))
        - Update code block syntax highlighting identifiers ([\#1367](https://github.com/ubccr/xdmod/pull/1367))
        - Update PHP and MySQL version requirements ([\#1364](https://github.com/ubccr/xdmod/pull/1364))
        - Update processor buckets documentation ([\#1359](https://github.com/ubccr/xdmod/pull/1359))
        - Add GPU metrics documentation ([\#1355](https://github.com/ubccr/xdmod/pull/1355))
        - More detail for SSO setup ([\#1354](https://github.com/ubccr/xdmod/pull/1354))
        - Update documentation and example config for HTTPS by default ([\#1336](https://github.com/ubccr/xdmod/pull/1336))
        - Update shredder documentation ([\#1315](https://github.com/ubccr/xdmod/pull/1315))
        - Add roadmap link to documentation ([\#1197](https://github.com/ubccr/xdmod/pull/1197))
        - Update MySQL configuration suggestions ([\#1157](https://github.com/ubccr/xdmod/pull/1157))
    - Storage
        - Update storage documentation ([\#1403](https://github.com/ubccr/xdmod/pull/1403))
    - Cloud
        - Cloud documentation update for 9.0 ([\#1273](https://github.com/ubccr/xdmod/pull/1273))
- Bug Fixes
    - General
        - Improve CI validate script ([\#1399](https://github.com/ubccr/xdmod/pull/1399))
        - Replace bitwise operator with logical operator ([\#1398](https://github.com/ubccr/xdmod/pull/1398))
        - Rename internal dashboard button from "Dashboard" to "Admin Dashboard" ([\#1390](https://github.com/ubccr/xdmod/pull/1390))
        - Allow the internal dashboard to load even if a user account has an empty name. ([\#1382](https://github.com/ubccr/xdmod/pull/1382))
        - Fix groupby resource ([\#1374](https://github.com/ubccr/xdmod/pull/1374))
        - Use piped logging to rotate Apache logs ([\#1371](https://github.com/ubccr/xdmod/pull/1371))
        - Fix bugs in definition of cloud fact tables. ([\#1352](https://github.com/ubccr/xdmod/pull/1352))
        - Fix bugs in definition of job fact tables. ([\#1349](https://github.com/ubccr/xdmod/pull/1349))
        - Fix regression bug when paging datasets. ([\#1299](https://github.com/ubccr/xdmod/pull/1299))
        - Fix bug in highchart wrapper code. ([\#1285](https://github.com/ubccr/xdmod/pull/1285))
        - Fix bug viewing raw data as demo user. ([\#1284](https://github.com/ubccr/xdmod/pull/1284))
        - Display error message instead of "Loading..." ([\#1280](https://github.com/ubccr/xdmod/pull/1280))
        - Add updating of version in module portal settings ini files on upgrade ([\#1277](https://github.com/ubccr/xdmod/pull/1277))
        - Remove unnecessary redirect in the job viewer rest stack. ([\#1269](https://github.com/ubccr/xdmod/pull/1269))
        - Fix html decoding in Job Viewer -> Edit Search ([\#1268](https://github.com/ubccr/xdmod/pull/1268))
        - Fix bugs in Configuration classes ([\#1221](https://github.com/ubccr/xdmod/pull/1221))
        - Don't bother trying to set headers if not possible. ([\#1194](https://github.com/ubccr/xdmod/pull/1194))
        - Update organization affiliation change email ([\#1180](https://github.com/ubccr/xdmod/pull/1180))
        - Do not json_encode included file ([\#1141](https://github.com/ubccr/xdmod/pull/1141))
        - Focus on username field when login dialog is expanded ([\#1115](https://github.com/ubccr/xdmod/pull/1115))
        - Fix chart time display when the system timezone has a positive offset from UTC ([\#1035](https://github.com/ubccr/xdmod/pull/1035))
        - Fixed loading the organization abbrevation from organization.json ([\#951](https://github.com/ubccr/xdmod/pull/951))
    - Cloud
        - Minor Cleanup of the OpenStack event schema ([\#1396](https://github.com/ubccr/xdmod/pull/1396))
        - Convert date for cloud resource specifications to correct timezone from UTC ([\#1391](https://github.com/ubccr/xdmod/pull/1391))
        - Updates to cloud migration files to fix syntax issues ([\#1380](https://github.com/ubccr/xdmod/pull/1380))
        - Change unit to percent for cloud core utilization statistic ([\#1373](https://github.com/ubccr/xdmod/pull/1373))
        - Change table definition for vcpus field for the cloud_resource_specs table  ([\#1361](https://github.com/ubccr/xdmod/pull/1361))
        - Remove bug that causes stop events to create sessions ([\#1344](https://github.com/ubccr/xdmod/pull/1344))
        - Move UpdateCloudProjectToPI action to jobs-cloud-ingest-pi pipeline ([\#1313](https://github.com/ubccr/xdmod/pull/1313))
        - Improve performance of OpenStackCloudEventAssetRootVolumeIngestor and GenericCloudEventAssetRootVolumeIngestor actions ([\#1303](https://github.com/ubccr/xdmod/pull/1303))
        - Add alternate_group_by_column field to Project and Instance Type group by ([\#1295](https://github.com/ubccr/xdmod/pull/1295))
        - changing account, instance and instance_type tables to have global un… ([\#1272](https://github.com/ubccr/xdmod/pull/1272))
        - Change account, instance and instance_type tables to have single unique auto increment column ([\#1263](https://github.com/ubccr/xdmod/pull/1263))
        - Update openstack json event schema definition  ([\#1249](https://github.com/ubccr/xdmod/pull/1249))
        - Adding table name to remove ambiguity from group by statement ([\#1228](https://github.com/ubccr/xdmod/pull/1228))
        - Update cloud event schema and coerce data ([\#1144](https://github.com/ubccr/xdmod/pull/1144))
    - ETL
        - Update SQL delimiters ([\#1384](https://github.com/ubccr/xdmod/pull/1384))
        - Prevent LSF commands encoding warnings ([\#1377](https://github.com/ubccr/xdmod/pull/1377))
        - Convert Slurm job names from UTF-8 to ISO-8859-1 ([\#1363](https://github.com/ubccr/xdmod/pull/1363))
        - Throw expected exception when JSON is invalid ([\#1275](https://github.com/ubccr/xdmod/pull/1275))
        - Remove foreign key constraints from MyISAM table ([\#1232](https://github.com/ubccr/xdmod/pull/1232))
        - Fix typo in EtlConfiguration ([\#1222](https://github.com/ubccr/xdmod/pull/1222))
        - Fix the underlying JSON configuration parsing problems ([\#1131](https://github.com/ubccr/xdmod/pull/1131))
        - Make the Shredder ignore empty lines ([\#1088](https://github.com/ubccr/xdmod/pull/1088))
    - Open OnDemand
        - Add ability to apply filters to the aggregatedata request to suppport Open OnDemand integration. ([\#1372](https://github.com/ubccr/xdmod/pull/1372))
    - Job Viewer
        - Add DISTINCT to Jobs realm JobDataset query ([\#1332](https://github.com/ubccr/xdmod/pull/1332))
        - Update job viewer formatter to handle N/A case for byte data. ([\#1208](https://github.com/ubccr/xdmod/pull/1208))
        - Improve search history panel UI. ([\#1201](https://github.com/ubccr/xdmod/pull/1201))
    - Data Warehouse Export
        - Fix data export submission confirmation message ([\#1322](https://github.com/ubccr/xdmod/pull/1322))
        - Fix Data Warehouse Export UI download/cancel issues ([\#1143](https://github.com/ubccr/xdmod/pull/1143))
    - Report Generator
        - Switch to new report after "Save As" ([\#1302](https://github.com/ubccr/xdmod/pull/1302))
        - Rename report file name input field ([\#1281](https://github.com/ubccr/xdmod/pull/1281))
        - Add input validation to report generator controller endpoints. ([\#1219](https://github.com/ubccr/xdmod/pull/1219))
        - Fix report generation for annual and biannual reports. ([\#1216](https://github.com/ubccr/xdmod/pull/1216))
    - Metric Explorer
        - Fix some bugs in FilterListBuilder ([\#1254](https://github.com/ubccr/xdmod/pull/1254))
        - Fix multi-timeseries dataset-show-remainder bug ([\#1239](https://github.com/ubccr/xdmod/pull/1239))
        - Fix show remainder calculation for Jobs by System Username ([\#1238](https://github.com/ubccr/xdmod/pull/1238))
        - Fix stderr checkbox disablement in Metric Explorer. ([\#1184](https://github.com/ubccr/xdmod/pull/1184))
    - ACL
        - Enforce realm ACLs for "show raw data" endpoints ([\#1200](https://github.com/ubccr/xdmod/pull/1200))
        - Fix foreign key constraint on moddb report_template_acls ([\#1171](https://github.com/ubccr/xdmod/pull/1171))
        - Fix module filtering ([\#1170](https://github.com/ubccr/xdmod/pull/1170))
    - User Dashboard
        - Fix page size in JobComponent single user view. ([\#1195](https://github.com/ubccr/xdmod/pull/1195))
    - Internal Dashboard
        - Clear report cache operation will remove image files too. ([\#1181](https://github.com/ubccr/xdmod/pull/1181))
        - Updating Internal Dashboard Existing Email ([\#1095](https://github.com/ubccr/xdmod/pull/1095))
    - Storage
        - Fix storage realm username GroupBy ([\#1156](https://github.com/ubccr/xdmod/pull/1156))
- Enhancements
    - Metric Explorer
        - Update the utilization metric description ([\#1392](https://github.com/ubccr/xdmod/pull/1392))
        - Improve performance of the timeseries queries ([\#1264](https://github.com/ubccr/xdmod/pull/1264))
    - General
        - Adding an alert to notify users when ingestion has not been run. ([\#1389](https://github.com/ubccr/xdmod/pull/1389))
        - Update PHP version check ([\#1366](https://github.com/ubccr/xdmod/pull/1366))
        - Update publication and presentation listing for Open XDMoD About page ([\#1365](https://github.com/ubccr/xdmod/pull/1365))
        - Remove Roles and UserRole tables ([\#1360](https://github.com/ubccr/xdmod/pull/1360))
        - Enforce secure cookie flag for all session cookies and the REST token ([\#1356](https://github.com/ubccr/xdmod/pull/1356))
        - Change default log file permissions ([\#1333](https://github.com/ubccr/xdmod/pull/1333))
        - Update source installation log directory notes ([\#1331](https://github.com/ubccr/xdmod/pull/1331))
        - Change default log directory permissions ([\#1328](https://github.com/ubccr/xdmod/pull/1328))
        - Update xdmod-check-config script ([\#1327](https://github.com/ubccr/xdmod/pull/1327))
        - Use absolute acl-config path in xdmod-ingestor ([\#1306](https://github.com/ubccr/xdmod/pull/1306))
        - Update some wording for groupbys ([\#1297](https://github.com/ubccr/xdmod/pull/1297))
        - Improve ingestor option error checking ([\#1227](https://github.com/ubccr/xdmod/pull/1227))
    - Open OnDemand
        - Update `rest/v1/users/current` ([\#1370](https://github.com/ubccr/xdmod/pull/1370))
        - CORS changes needed for OpenOnDemand ([\#1351](https://github.com/ubccr/xdmod/pull/1351))
    - ETL
        - Update Slurm shredder to ignore non-ended job states ([\#1362](https://github.com/ubccr/xdmod/pull/1362))
        - Add PBS jobs realm GPU data ([\#1353](https://github.com/ubccr/xdmod/pull/1353))
        - Add `job_id_raw` to SGE shredder ([\#1317](https://github.com/ubccr/xdmod/pull/1317))
        - Change ETL logging identification strings ([\#1316](https://github.com/ubccr/xdmod/pull/1316))
        - Check engine during foreign key constraint verification ([\#1259](https://github.com/ubccr/xdmod/pull/1259))
        - Add additional ETL DB Model error checking ([\#1237](https://github.com/ubccr/xdmod/pull/1237))
    - Data Warehouse Export
        - Raw stats SUPREMM refactoring ([\#1343](https://github.com/ubccr/xdmod/pull/1343))
        - Add README to data warehouse export zip files ([\#1300](https://github.com/ubccr/xdmod/pull/1300))
        - Update data warehouse export UI ([\#1298](https://github.com/ubccr/xdmod/pull/1298))
        - Improve data warehouse export logging ([\#1223](https://github.com/ubccr/xdmod/pull/1223))
        - Minor change to Data Warehouse Export controller ([\#1166](https://github.com/ubccr/xdmod/pull/1166))
        - Prevent creation of duplicate export requests ([\#1150](https://github.com/ubccr/xdmod/pull/1150))
        - Refactor export request deleted state ([\#1149](https://github.com/ubccr/xdmod/pull/1149))
    - Internal Dashboard
        - Add index to log table ([\#1330](https://github.com/ubccr/xdmod/pull/1330))
    - Infrastructure
        - Add support for multiple groupby definition files in js etl. ([\#1301](https://github.com/ubccr/xdmod/pull/1301))
        - Reduce the roundoff error in the Avg of N others calculation. ([\#1248](https://github.com/ubccr/xdmod/pull/1248))
    - User Dashboard
        - Move the job grid into its own class ([\#1283](https://github.com/ubccr/xdmod/pull/1283))
    - Report Generator
        - Close report generator "Save As" dialog after saving ([\#1279](https://github.com/ubccr/xdmod/pull/1279))
    - ACL
        - Make acl-config more resilient ([\#1188](https://github.com/ubccr/xdmod/pull/1188))
        - Change `acl-config` backup tables detected error message ([\#1164](https://github.com/ubccr/xdmod/pull/1164))
- New Features
    - Infrastructure
        - Adding the new ubccr/simplesamlphp-module-authoidcoauth2 module ([\#1378](https://github.com/ubccr/xdmod/pull/1378))
    - Cloud
        - Add hierarchy group by's to Cloud realm ([\#1309](https://github.com/ubccr/xdmod/pull/1309))
        - Add PI information for the Cloud realm ([\#1286](https://github.com/ubccr/xdmod/pull/1286))
        - Add cloud core hour utilization statistic ([\#1242](https://github.com/ubccr/xdmod/pull/1242))
        - Ingest cloud resource specifications ([\#1199](https://github.com/ubccr/xdmod/pull/1199))
    - ETL
        - Add GPU tracking to Jobs realm ([\#1270](https://github.com/ubccr/xdmod/pull/1270))
        - Add referenced schema to foreign key constraints ([\#1252](https://github.com/ubccr/xdmod/pull/1252))
    - General
        - Re-implementation of the data warehouse Group By and Statistic code ([\#1192](https://github.com/ubccr/xdmod/pull/1192))
        - Allow JSON references to overwrite keys ([\#1142](https://github.com/ubccr/xdmod/pull/1142))
- Qa / Testing
    - Infrastructure
        - Update PhantomJS unit tests ([\#1325](https://github.com/ubccr/xdmod/pull/1325))
    - General
        - Improve robustness of integration and regression tests. ([\#1256](https://github.com/ubccr/xdmod/pull/1256))
        - Get rid of unneeded symbolic link ([\#1255](https://github.com/ubccr/xdmod/pull/1255))
        - Fix race condition in test harness ([\#1173](https://github.com/ubccr/xdmod/pull/1173))
    - Metric Explorer
        - Added regression tests for chart filters. ([\#1250](https://github.com/ubccr/xdmod/pull/1250))
- Experiment
    - General
        - Gateways Realm dead simple start ([\#1262](https://github.com/ubccr/xdmod/pull/1262))
- Dependencies
    - General
        - Bump symfony/http-foundation from 2.8.50 to 2.8.52 ([\#1174](https://github.com/ubccr/xdmod/pull/1174))
        - Bump robrichards/xmlseclibs from 3.0.3 to 3.0.4 ([\#1161](https://github.com/ubccr/xdmod/pull/1161))
- Data Quality
    - General
        - Update statistics and groupbys for consistency ([\#1089](https://github.com/ubccr/xdmod/pull/1089))

## 2019-10-29 v8.5.1

- Bug Fixes
    - General
        - Fix bug parsing the `resources.json` configuration file when a single resource is defined in the file. ([\#1130](https://github.com/ubccr/xdmod/pull/1130))

## 2019-10-21 v8.5.0

- Bug Fixes
    - General
        - Ensure resourcetypes table is correctly populated. ([PR \#1108](https://github.com/ubccr/xdmod/pull/1108))
        - Ensure summary controller only displays preset charts for realm… ([PR \#1096](https://github.com/ubccr/xdmod/pull/1096))
        - `xdmod-update-resource-specs`: fix resources file name ([PR \#1085](https://github.com/ubccr/xdmod/pull/1085))
        - Enable APCu cache for Configuration objects ([PR \#952](https://github.com/ubccr/xdmod/pull/952))
        - Fix Query Descriptor Visibility ([PR \#911](https://github.com/ubccr/xdmod/pull/911))
    - Job Viewer
        - Fix bug in job viewer save search when multiple realms available ([PR \#1092](https://github.com/ubccr/xdmod/pull/1092))
        - Fix display bugs in the error message for the job viewer analytics. ([PR \#1003](https://github.com/ubccr/xdmod/pull/1003))
    - Internal Dashboard
        - Fixing an infinite loop when discarding user changes ([PR \#1068](https://github.com/ubccr/xdmod/pull/1068))
    - Metric Explorer
        - Fix metric explorer metric and dimension display bug. ([PR \#1046](https://github.com/ubccr/xdmod/pull/1046))
        - Enable aggregate view for all "Per Job" statistics in the Jobs realm. ([PR \#961](https://github.com/ubccr/xdmod/pull/961))
    - User Dashboard
        - Bug fixes: Add mask and make chart name unique ([PR \#1028](https://github.com/ubccr/xdmod/pull/1028))
        - adding check to make sure Help Tips exist before making a help tour for a portlet ([PR \#1011](https://github.com/ubccr/xdmod/pull/1011))
        - Bug Fixes and Changes ([PR \#979](https://github.com/ubccr/xdmod/pull/979))
    - ETL
        - Improve DirectorySanner to properly handle first file being empty ([PR \#931](https://github.com/ubccr/xdmod/pull/931))
    - ACL
        - Fix backup table detection bug in `acl-config`.  ([PR \#920](https://github.com/ubccr/xdmod/pull/920))
- Enhancements
    - Infrastructure
        - Update DWI's `isRealmEnabled` to be config file based ([PR \#1102](https://github.com/ubccr/xdmod/pull/1102))
        - Filter Realms processed by `acl-config` ([PR \#1000](https://github.com/ubccr/xdmod/pull/1000))
    - ACL
        - Updated to add resource_types and resource_type_realms ([PR \#1006](https://github.com/ubccr/xdmod/pull/1006))
    - Job Viewer
        - Add tooltips to jobviewer Summary value column ([PR \#984](https://github.com/ubccr/xdmod/pull/984))
        - Convert Joules to kWh for display in job viewer. ([PR \#983](https://github.com/ubccr/xdmod/pull/983))
        - Add Show raw data support for the Jobs Realm ([PR \#900](https://github.com/ubccr/xdmod/pull/900))
    - General
        - Performance improvements by providing caching for Configuration objects built from JSON configuration files ([PR \#950](https://github.com/ubccr/xdmod/pull/950))
    - ETL
        - Allow re-use of StructuredFile data endpoints with external filters ([PR \#944](https://github.com/ubccr/xdmod/pull/944))
- Qa / Testing
    - General
        - Add Usage Explorer Tests ([PR \#1062](https://github.com/ubccr/xdmod/pull/1062))
        - Multi realm installs - Allow for modification of enabled realms… ([PR \#1004](https://github.com/ubccr/xdmod/pull/1004))
        - Improve robustness of UI tests ([PR \#994](https://github.com/ubccr/xdmod/pull/994))
        - Update Travis YML to explicitly use Ubuntu 14.04 ([PR \#959](https://github.com/ubccr/xdmod/pull/959))
        - Fixing timing issues in Metric Explorer UI Tests ([PR \#953](https://github.com/ubccr/xdmod/pull/953))
        - Add Dockerfiles used for test builds ([PR \#935](https://github.com/ubccr/xdmod/pull/935))
        - Less stringent DataWarehouse Descripter tests ([PR \#926](https://github.com/ubccr/xdmod/pull/926))
        - enable all component tests and make sure they work ([PR \#902](https://github.com/ubccr/xdmod/pull/902))
    - Infrastructure
        - Wait for loading mask to disappear on SSO logout ([PR \#927](https://github.com/ubccr/xdmod/pull/927))
- Documentation
    - General
        - Improve xdmod-ingestor documentation ([PR \#1016](https://github.com/ubccr/xdmod/pull/1016))
        - Improve processor buckets documentation ([PR \#972](https://github.com/ubccr/xdmod/pull/972))
- New Features
    - Data Warehouse Export
        - Add data warehouse batch export ([PR \#1010](https://github.com/ubccr/xdmod/pull/1010))
    - Metric Explorer
        - Add a view chart json button for developers ([PR \#988](https://github.com/ubccr/xdmod/pull/988))
        - Add chart link button to metric explorer ([PR \#974](https://github.com/ubccr/xdmod/pull/974))
    - User Dashboard
        - Added reset summary page layout UI ([PR \#982](https://github.com/ubccr/xdmod/pull/982))
        - Add JobPortlet ([PR \#976](https://github.com/ubccr/xdmod/pull/976))
        - Adding New User Help Tour and functionality to reset if a user has seen a tour or not ([PR \#971](https://github.com/ubccr/xdmod/pull/971))
        - Add recent charts and reports portlet ([PR \#968](https://github.com/ubccr/xdmod/pull/968))
        - Add report thumbnails portlet ([PR \#967](https://github.com/ubccr/xdmod/pull/967))
        - Add guided user tours ([PR \#962](https://github.com/ubccr/xdmod/pull/962))
        - User Dashboard - Center Report Card: Support ([PR \#943](https://github.com/ubccr/xdmod/pull/943))
        - User Dashboard - Summary Statistics Portlet ([PR \#930](https://github.com/ubccr/xdmod/pull/930))
        - Initial Prototype of Novice User Portal ([PR \#909](https://github.com/ubccr/xdmod/pull/909))
- Data Quality
    - General
        - Increase size of system username column ([PR \#1007](https://github.com/ubccr/xdmod/pull/1007))
        - Update Average Wall Hours statistic to be exact rather than approximate in aggregate mode ([PR \#964](https://github.com/ubccr/xdmod/pull/964))
        - Fix datatype of job_id column ([PR \#932](https://github.com/ubccr/xdmod/pull/932))

## 2019-05-06 v8.1.2

- Bug Fixes
    - General
        - Update `isRealmEnabled` sql ([\#912](https://github.com/ubccr/xdmod/pull/912))
        - Add storage bootstrap to setup ([\#914](https://github.com/ubccr/xdmod/pull/914))
        - Add jobs cloud common pipeline to cloud ingestion ([\#916](https://github.com/ubccr/xdmod/pull/916))

## 2019-05-02 v8.1.1

- Bug Fixes
    - General
        - Allow upgrade to finish if cloud realm schema has not been created ([\#882](https://github.com/ubccr/xdmod/pull/882))

## 2019-04-23 v8.1.0

- Documentation
    - ETL
        - Improve storage aggregation ([\#882](https://github.com/ubccr/xdmod/pull/882))
        - Processor Buckets documentation update ([\#881](https://github.com/ubccr/xdmod/pull/881))
    - General
        - Update broken url and change wording of supported software ([\#876](https://github.com/ubccr/xdmod/pull/876))
        - Add Federated XDMoD to About page ([\#873](https://github.com/ubccr/xdmod/pull/873))
        - Update with some of the options that are avalbile ([\#804](https://github.com/ubccr/xdmod/pull/804))
        - Improve storage documentation ([\#771](https://github.com/ubccr/xdmod/pull/771))
    - Cloud
        - Updated Cloud Documentation for 8.1 Release ([\#875](https://github.com/ubccr/xdmod/pull/875))
- New Features
    - Cloud
        - Add cloud user and system account group by ([\#797](https://github.com/ubccr/xdmod/pull/797))
        - Add support for cloud data to xdmod-shredder and xdmod-ingestor ([\#739](https://github.com/ubccr/xdmod/pull/739))
- Enhancements
    - General
        - Add support for configurable email subject prefix ([\#872](https://github.com/ubccr/xdmod/pull/872))
        - Add node_modules to the RPM. ([\#835](https://github.com/ubccr/xdmod/pull/835))
        - Update config read order ([\#818](https://github.com/ubccr/xdmod/pull/818))
        - Add initial summary charts for the cloud realm ([\#803](https://github.com/ubccr/xdmod/pull/803))
        - Support simplesaml's internal session naming ([\#757](https://github.com/ubccr/xdmod/pull/757))
        - Support asynchronous loading of Usage tab thumbnail charts ([\#750](https://github.com/ubccr/xdmod/pull/750))
        - Update Job Viewer API to support multiple realms ([\#733](https://github.com/ubccr/xdmod/pull/733))
        - Support non-numeric values for Usage chart filter parameters ([\#716](https://github.com/ubccr/xdmod/pull/716))
        - Make "Show raw data" for multiple realms configurable ([\#706](https://github.com/ubccr/xdmod/pull/706))
        - Update Sign On Panel to collapse local login if SSO is enabled ([\#701](https://github.com/ubccr/xdmod/pull/701))
    - Cloud
        - Do not truncate aggregate tables on each ingest ([\#841](https://github.com/ubccr/xdmod/pull/841))
        - Truncate staging tables after ingestion ([\#778](https://github.com/ubccr/xdmod/pull/778))
        - Update events that are used to determine VM session starts and stops ([\#732](https://github.com/ubccr/xdmod/pull/732))
    - ETL
        - Improve resiliency of ETLv2 manage tables ([\#807](https://github.com/ubccr/xdmod/pull/807))
        - Add storage shredder/ingestor support ([\#786](https://github.com/ubccr/xdmod/pull/786))
        - Support ETL '$include' directive ([\#785](https://github.com/ubccr/xdmod/pull/785))
        - Update Configuration class to support merging objects in local config files ([\#782](https://github.com/ubccr/xdmod/pull/782))
        - DirectoryScanner support for last modified time based on filename and/or directory ([\#780](https://github.com/ubccr/xdmod/pull/780))
        - Move the job performance postprocessing SQL to the aggregation pipeline ([\#770](https://github.com/ubccr/xdmod/pull/770))
        - Implement dynamic fact tables via ETLv2 for job performance ETL ([\#742](https://github.com/ubccr/xdmod/pull/742))
        - Add exception code to logAndThrowException ([\#719](https://github.com/ubccr/xdmod/pull/719))
        - Skip only records that fail verificaiton instead of rest of file ([\#714](https://github.com/ubccr/xdmod/pull/714))
- Bug Fixes
    - Cloud
        - Fix roles file comparison to use object and not string ([\#878](https://github.com/ubccr/xdmod/pull/878))
        - Update session_records timestamps to non-nullable for MySQL 5.7 support ([\#877](https://github.com/ubccr/xdmod/pull/877))
        - Add cloud raw tables to cloud manage tables action ([\#874](https://github.com/ubccr/xdmod/pull/874))
        - Change staging table to use 1 as unknown id instead of -1 ([\#866](https://github.com/ubccr/xdmod/pull/866))
        - Update GroupBys In Cloud Aggregate Table ([\#863](https://github.com/ubccr/xdmod/pull/863))
        - Change cloud person username fields to be not null ([\#860](https://github.com/ubccr/xdmod/pull/860))
        - Change where staging action gets user id from for cloud data ([\#845](https://github.com/ubccr/xdmod/pull/845))
        - Remove event_id from the event table primary key ([\#844](https://github.com/ubccr/xdmod/pull/844))
        - Prevent null usernames being added when ingesting cloud data ([\#838](https://github.com/ubccr/xdmod/pull/838))
        - Remove duplicate join statement that was causing 1066 Not unique table/alias error ([\#837](https://github.com/ubccr/xdmod/pull/837))
        - Fix for generic cloud datetime, and openstack instance type datetime ([\#820](https://github.com/ubccr/xdmod/pull/820))
        - Update cloud realm to not throw away event precision ([\#811](https://github.com/ubccr/xdmod/pull/811))
        - Guarantees a deterministic order for events received by the event reconstructor ([\#805](https://github.com/ubccr/xdmod/pull/805))
    - General
        - Add proper namespace in VerifyDatabase ([\#819](https://github.com/ubccr/xdmod/pull/819))
        - Fix path to Exception in TimeAggregationUnit::factory ([\#810](https://github.com/ubccr/xdmod/pull/810))
        - Explictly check for stdClass in VariableStore initializer ([\#802](https://github.com/ubccr/xdmod/pull/802))
        - Make lastLogin time be floored to the second instead of microtime ([\#755](https://github.com/ubccr/xdmod/pull/755))
        - Make logrotate.d file not override global settings ([\#749](https://github.com/ubccr/xdmod/pull/749))
        - Fix security vulnerabities in job performance ([\#738](https://github.com/ubccr/xdmod/pull/738))
        - Allow search panel scroll bars on small displays ([\#702](https://github.com/ubccr/xdmod/pull/702))
    - ETL
        - Update primary key on resourcefact table to improve cloud ingestion ([\#795](https://github.com/ubccr/xdmod/pull/795))
        - Throw Exception if lockfile could not be obtained ([\#793](https://github.com/ubccr/xdmod/pull/793))
        - Improve debugging messages when executing SQL ([\#783](https://github.com/ubccr/xdmod/pull/783))
        - Don't automatically rewind DirectoryScanner file handle ([\#768](https://github.com/ubccr/xdmod/pull/768))
        - Explicitly cast potentiall nulls to array for array_merge() ([\#756](https://github.com/ubccr/xdmod/pull/756))
        - Improve verification of resource codes ([\#720](https://github.com/ubccr/xdmod/pull/720))
        - Fix uncaught ETL exception in PdoIngestor ([\#718](https://github.com/ubccr/xdmod/pull/718))
    - Metric Explorer
        - Fix refesh button for Metric Explorer ([\#740](https://github.com/ubccr/xdmod/pull/740))
- QA / Testing
    - General
        - Speedup integration tests ([\#850](https://github.com/ubccr/xdmod/pull/850))
        - Clean up linker.php ([\#847](https://github.com/ubccr/xdmod/pull/847))
        - Add environment variable to force regression test harness to generate expected results ([\#848](https://github.com/ubccr/xdmod/pull/848))
        - Enforce javascript unit tests ([\#766](https://github.com/ubccr/xdmod/pull/766))
        - Robustness improvements for integration tests ([\#726](https://github.com/ubccr/xdmod/pull/726))
    - Cloud
        - Update cloud reference data ([\#827](https://github.com/ubccr/xdmod/pull/827))

## 2018-10-30 v8.0.0

- Features
    - General
        - Added a **beta** version of the Cloud realm to provide metrics relevant to cloud computing resources.
        - Added a **beta** version of the Storage realm to provide metrics relevant to storage systems installed at a center.
        - Federated XDMoD has been released for production. Federated XDMoD allows individual, locally managed, XDMoD instances to report all or a subset of their accounting data to a central Hub which provides a global view of the federation.
        - All XDMoD user profiles are now associated with an organization. Previously, this was only required for Campus Champions.
        - Added support for automatically detecting / assigning a new SSO User's organization.
        - Added support for automatically detecting if a user's organization has changed and updating their accounts accordingly. This may include, but is not limited to, the removal of elevated privileges.
        - Hardened the login and password reset process as a result of a security audit by University of Cambridge.
        - Improved support for resource manager job arrays.
        - Many improvements to the documentation.
    - ETL
        - Reorganized several ETL pipelines.
        - Improved data sanitization for tighter checks present in MySQL 5.7.
        - Refactored Jobs realm ingestion to utilize ETLv2.
        - Standardize action names to follow the format module.pipeline.action. For example, xdmod.acls.manage-tables.
        - Added character set and coalition to table definitions.
        - Added support for foreign key constraints.
        - Added support for the definition of ETL variables on the command line using -d variable=value.
        - Add ingestion of node hostname data from SGE logs.
        - Various ETL performance improvements.
- Bug Fixes
    - User Interface
        - Deep linking when logged in using SSO has been restored.
        - Update the logrotate configuration to use the su and create options.
    - ETL
        - Add primary keys to select ETL source queries.
        - When modifying an existing table, preserve the order of the columns in the definition file.
        - Ensure that file handles are flushed before inserting the final chunk of data.
    - Misc
        - Fixed several exceptions that were outside of a namespace.
        - Fixed an issue where ACLs were not properly created on upgrade.
        - Several minor bugfixes

## 2018-05-23 v7.5.1

- Bug Fixes
    - Properly implement data access for non-feature ACLs (e.g., ACLs that provide access to data
      but not a feature such as a tab) that are not part of the hierarchy.  For example, the Value
      Analytics ACL.

## 2018-03-01 v7.5.0

- Features
    - General
        - Improve performance of Utilization statistic
        - Do not embed JavaScript in chart objects returned by the back end, instead
        - include this code directly in the user interface code
        - General improvements to the performance of the REST stack
        - Updated Google Captcha to v2 (v1 is now discontinued)
        - Added numerous component and unit tests
        - Removed unused code paths
        - Update greenlion/PHP-SQL-Parser to newer release that does not autoload itself
    - User Interface
        - Legend item edit box is now displayed next to the legend item that is being edited and is larger to accommodate longer strings
        - Added a "Select All" button to the filter dialog
        - Added PDF export capability, which greatly improves the quality of images included in LATEX documents
    - ETL
        - When ingesting Slurm data, return duplicate jobs such as those that were resubmitted due to node fail conditions
        - Improve performance of filter list generation
    - Application Kernels
        - Several minor bug fixes and UI improvements
    - Bug Fixes
    - General
        - Fix the Show Guidelines button in the Usage tab
        - Fixed an issue with data filtering when an unprivileged user tries to view timeseries data for a restricted realm or statistic
    - User Interface
        - Fix the "TypeError: element is undefined" error when plotting Pie chart in metric explorer

## 2018-02-16 v7.1.0

- Features
    - General
        - Added support for Globus as a federated authentication provider
        - Improvements to the user login dialog and matching of users to institutions
        - Added the ability to use fine-grained ACLs for controlling access to features such as tabs and realms as well as data
        - Code pertaining to creating/sending emails has been moved to a central location.
        - Improved online documentation for installations and upgrades
    - Internal Admin Dashboard
        - Updated user management functionality in the administrative dashboard to support fine-grained ACLs for individual users
        - Many stability improvements to the administrator dashboard, especially for managing users
    - Storage Realm
        - Added alpha version of the Storage realm to track resource storage utilization
    - ETL
        - Added an ETL pipeline for ingesting log files generated by Eucalyptus clouds
        - Added support for PBS/Torque logs where the host of a job is not included in the job's ID string
        - Support references into complex source records such as JSON objects

- Bug Fixes
    - General
        - Improved error reporting
        - Fixed several issues where JSON was not properly encoded
        - For end dates that fall on the current date or in the future, do not automatically adjust the date to the end of the aggregation period that it falls into.
    - Report Generator
        - Make timeframes editable for individual charts
    - ETL
        - Ignore duplicate hosts found in LSF accounting log files
        - Don't verify data endpoints associated with disbaled actions
        - Always regenerate source data queries prior to execution to ensure that any modified ETL variables are properly applied
        - Update host list parser to ensure that empty host names are not returned
        - Remove PHP memory limit when running ETL pipelines

## 2017-09-27 v7.0.1

- Bug Fixes
    - General
        - Fixed compatibility with PHP 5.3.3 ([\#269](https://github.com/ubccr/xdmod/pull/269))

## 2017-09-21 v7.0.0

- Features
    - General
        - Enhanced authorization framework ([\#97](https://github.com/ubccr/xdmod/pull/97), [\#146](https://github.com/ubccr/xdmod/pull/146), [\#206](https://github.com/ubccr/xdmod/pull/206))
        - Improved login prompt to automatically appear when an unauthenticated user attempts to access any private tab ([\#110](https://github.com/ubccr/xdmod/pull/110))
        - Improved design of Metric Explorer Load Chart menu ([\#144](https://github.com/ubccr/xdmod/pull/144))
        - Re-enabled aggregate mode for "Wall Hours: Per Job" metric ([\#186](https://github.com/ubccr/xdmod/pull/186))
        - Added quarterly report template for center directors ([\#199](https://github.com/ubccr/xdmod/pull/199))
        - Improved support for third-party PHP libraries used by modules ([\#205](https://github.com/ubccr/xdmod/pull/205))
    - ETLv2
        - Added support for RFC-6901 JSON References ([\#100](https://github.com/ubccr/xdmod/pull/100), [\#166](https://github.com/ubccr/xdmod/pull/166))
        - Added file directory data endpoint ([\#154](https://github.com/ubccr/xdmod/pull/154))
        - Improved support for `order_id` columns in Open XDMoD database ([\#201](https://github.com/ubccr/xdmod/pull/201))
        - Refactored various components to support new features ([\#138](https://github.com/ubccr/xdmod/pull/138), [\#145](https://github.com/ubccr/xdmod/pull/145), [\#151](https://github.com/ubccr/xdmod/pull/151), [\#167](https://github.com/ubccr/xdmod/pull/167), [\#174](https://github.com/ubccr/xdmod/pull/174), [\#180](https://github.com/ubccr/xdmod/pull/180), [\#196](https://github.com/ubccr/xdmod/pull/196))
    - Job Viewer
        - Added Gantt chart view of job peers ([\#153](https://github.com/ubccr/xdmod/pull/153), [\#164](https://github.com/ubccr/xdmod/pull/164))
        - Added ability to link directly to jobs ([\#156](https://github.com/ubccr/xdmod/pull/156))
        - Improved user feedback while job data is loading ([\#168](https://github.com/ubccr/xdmod/pull/168))
- Bug Fixes
    - General
        - Fixed various compatibility issues with PHP 7 ([\#101](https://github.com/ubccr/xdmod/pull/101), [\#183](https://github.com/ubccr/xdmod/pull/183))
        - Fixed handling of 4-byte UTF-8 characters during XRAS ingestion ([\#122](https://github.com/ubccr/xdmod/pull/122))
        - Improved handling of invalid start and end dates received by API ([\#160](https://github.com/ubccr/xdmod/pull/160))
        - Fixed validation of length of names in contact forms ([\#175](https://github.com/ubccr/xdmod/pull/175))
        - Improved handling of jobs with "0" start or end time ([\#197](https://github.com/ubccr/xdmod/pull/197))
        - Fixed case where About tab would display a blank page when loaded using Chrome ([\#232](https://github.com/ubccr/xdmod/pull/232))
    - Job Viewer
        - Fixed directions not always appearing when all jobs are closed ([\#155](https://github.com/ubccr/xdmod/pull/155))
        - Fixed memory leak ([\#149](https://github.com/ubccr/xdmod/pull/149))
        - Fixed duplicate search nodes being created when opening jobs from Metric Explorer charts with "\#" in the title ([\#236](https://github.com/ubccr/xdmod/pull/236))
    - Metric Explorer
        - Fixed handling of "%" characters in Metric Explorer options when using Firefox ([\#114](https://github.com/ubccr/xdmod/pull/114))
        - Fixed XSS vulnerability involving chart names ([\#239](https://github.com/ubccr/xdmod/pull/239))
    - Usage
        - Fixed error bars option being enabled when error bars are not available ([\#188](https://github.com/ubccr/xdmod/pull/188))
        - Fixed metrics appearing to be available to users that do not have access ([\#189](https://github.com/ubccr/xdmod/pull/189))
- Miscellaneous
    - Moved Node.js ETL framework to Open XDMoD repository ([\#106](https://github.com/ubccr/xdmod/pull/106))
    - Fixed build script running out of memory allocated by PHP ([\#118](https://github.com/ubccr/xdmod/pull/118))
    - Performed work in anticipation of federated instances ([\#148](https://github.com/ubccr/xdmod/pull/148))
    - Improved development workflow ([\#124](https://github.com/ubccr/xdmod/pull/124), [\#157](https://github.com/ubccr/xdmod/pull/157), [\#195](https://github.com/ubccr/xdmod/pull/195))
    - Improved quality assurance ([\#107](https://github.com/ubccr/xdmod/pull/107), [\#116](https://github.com/ubccr/xdmod/pull/116), [\#134](https://github.com/ubccr/xdmod/pull/134), [\#143](https://github.com/ubccr/xdmod/pull/143), [\#150](https://github.com/ubccr/xdmod/pull/150), [\#163](https://github.com/ubccr/xdmod/pull/163), [\#165](https://github.com/ubccr/xdmod/pull/165), [\#169](https://github.com/ubccr/xdmod/pull/169), [\#173](https://github.com/ubccr/xdmod/pull/173), [\#184](https://github.com/ubccr/xdmod/pull/184), [\#185](https://github.com/ubccr/xdmod/pull/185), [\#187](https://github.com/ubccr/xdmod/pull/187), [\#190](https://github.com/ubccr/xdmod/pull/190), [\#193](https://github.com/ubccr/xdmod/pull/193), [\#194](https://github.com/ubccr/xdmod/pull/194), [\#198](https://github.com/ubccr/xdmod/pull/198), [\#212](https://github.com/ubccr/xdmod/pull/212), [\#235](https://github.com/ubccr/xdmod/pull/235))
    - Cleaned up old and/or unused code ([\#104](https://github.com/ubccr/xdmod/pull/104), [\#105](https://github.com/ubccr/xdmod/pull/105), [\#109](https://github.com/ubccr/xdmod/pull/109), [\#112](https://github.com/ubccr/xdmod/pull/112), [\#117](https://github.com/ubccr/xdmod/pull/117), [\#128](https://github.com/ubccr/xdmod/pull/128), [\#158](https://github.com/ubccr/xdmod/pull/158), [\#159](https://github.com/ubccr/xdmod/pull/159), [\#182](https://github.com/ubccr/xdmod/pull/182), [\#191](https://github.com/ubccr/xdmod/pull/191), [\#213](https://github.com/ubccr/xdmod/pull/213))
    - Improved documentation ([\#161](https://github.com/ubccr/xdmod/pull/161), [\#202](https://github.com/ubccr/xdmod/pull/202), [\#203](https://github.com/ubccr/xdmod/pull/203), [\#229](https://github.com/ubccr/xdmod/pull/229), [\#247](https://github.com/ubccr/xdmod/pull/247))

2017-05-11 v6.6.0
-----------------

- Features
    - General
        - Added ability to group realms together under categories
          ([\#60](https://github.com/ubccr/xdmod/pull/60))
          - Categories have taken the place of realms in the user interface
        - Improved support for browser client assets provided by modules
          ([\#82](https://github.com/ubccr/xdmod/pull/82),
           [\#113](https://github.com/ubccr/xdmod/pull/113))
        - Improved upgrade messaging
          ([\#86](https://github.com/ubccr/xdmod/pull/86))
    - ETLv2
        - Added per-pipeline ETL locks
          ([\#10](https://github.com/ubccr/xdmod/pull/10))
        - Enhanced multi-host aggregation
          ([\#13](https://github.com/ubccr/xdmod/pull/13))
        - Added read support for Oracle endpoints
          ([\#34](https://github.com/ubccr/xdmod/pull/34))
        - Improved support for running pipelines over all dates
          ([\#77](https://github.com/ubccr/xdmod/pull/77))
        - Improved transformation support
          ([\#80](https://github.com/ubccr/xdmod/pull/80))
        - Added a tool for comparing SQL tables
          ([\#78](https://github.com/ubccr/xdmod/pull/78))
        - Added other improvements
          ([\#43](https://github.com/ubccr/xdmod/pull/43),
           [\#45](https://github.com/ubccr/xdmod/pull/45),
           [\#84](https://github.com/ubccr/xdmod/pull/84),
           [\#90](https://github.com/ubccr/xdmod/pull/90),
           [\#92](https://github.com/ubccr/xdmod/pull/92))
- Bug Fixes
    - General
        - Fixed warning that could appear when using federated authentication
          ([\#19](https://github.com/ubccr/xdmod/pull/19))
        - Fixed unnecessary rounding in processor count statistics
          ([\#25](https://github.com/ubccr/xdmod/pull/25))
        - Fixed errors when attempting to preview an unsaved report
          ([\#26](https://github.com/ubccr/xdmod/pull/26))
        - Fixed aggregators excluding data on certain time boundaries
          ([\#47](https://github.com/ubccr/xdmod/pull/47))
        - Fixed handling of backslashes when using certain MySQL features
          ([\#52](https://github.com/ubccr/xdmod/pull/52),
           [\#53](https://github.com/ubccr/xdmod/pull/53))
        - Fixed non-aggregate metrics being allowed in aggregate datasets
          ([\#74](https://github.com/ubccr/xdmod/pull/74))
        - Fixed REST API throwing non-standard errors
          ([\#87](https://github.com/ubccr/xdmod/pull/87))
        - Fixed display error with password reset form that occurred when using
          federated authentication
          ([\#108](https://github.com/ubccr/xdmod/pull/108))
        - Fixed "Show chart title" option in Usage tab not working as expected
          ([\#139](https://github.com/ubccr/xdmod/pull/139))
    - Metric Explorer
        - Fixed Y-axis context menu disappearing after
          changing between linear and log scales
          ([\#12](https://github.com/ubccr/xdmod/pull/12))
        - Fixed typing certain characters causing chart options menu to close
          when using Firefox
          ([\#119](https://github.com/ubccr/xdmod/pull/119))
        - Fixed chart errors not displaying if help graphic is active
          ([\#121](https://github.com/ubccr/xdmod/pull/121))
        - Fixed chart last modified time not updating
          ([\#140](https://github.com/ubccr/xdmod/pull/140))
- Miscellaneous
    - Performed work in anticipation of allocations/accounts data in Open XDMoD
      ([\#11](https://github.com/ubccr/xdmod/pull/11),
       [\#50](https://github.com/ubccr/xdmod/pull/50))
    - Performed work in anticipation of cloud data in Open XDMoD
      ([\#68](https://github.com/ubccr/xdmod/pull/68),
       [\#75](https://github.com/ubccr/xdmod/pull/75))
    - Cleaned up old and/or unused code
      ([\#54](https://github.com/ubccr/xdmod/pull/54),
       [\#55](https://github.com/ubccr/xdmod/pull/55),
       [\#56](https://github.com/ubccr/xdmod/pull/56),
       [\#57](https://github.com/ubccr/xdmod/pull/57),
       [\#73](https://github.com/ubccr/xdmod/pull/73),
       [\#88](https://github.com/ubccr/xdmod/pull/88))
    - Improved logging
      ([\#29](https://github.com/ubccr/xdmod/pull/29),
       [\#40](https://github.com/ubccr/xdmod/pull/40),
       [\#46](https://github.com/ubccr/xdmod/pull/46),
       [\#50](https://github.com/ubccr/xdmod/pull/50),
       [\#66](https://github.com/ubccr/xdmod/pull/66),
       [\#98](https://github.com/ubccr/xdmod/pull/98))
    - Improved quality assurance
      ([\#21](https://github.com/ubccr/xdmod/pull/21),
       [\#27](https://github.com/ubccr/xdmod/pull/27),
       [\#28](https://github.com/ubccr/xdmod/pull/28),
       [\#35](https://github.com/ubccr/xdmod/pull/35),
       [\#41](https://github.com/ubccr/xdmod/pull/41),
       [\#48](https://github.com/ubccr/xdmod/pull/48),
       [\#58](https://github.com/ubccr/xdmod/pull/58),
       [\#67](https://github.com/ubccr/xdmod/pull/67),
       [\#76](https://github.com/ubccr/xdmod/pull/76),
       [\#79](https://github.com/ubccr/xdmod/pull/79),
       [\#93](https://github.com/ubccr/xdmod/pull/93),
       [\#94](https://github.com/ubccr/xdmod/pull/94))
    - Improved documentation
      ([\#32](https://github.com/ubccr/xdmod/pull/32),
       [\#37](https://github.com/ubccr/xdmod/pull/37),
       [\#44](https://github.com/ubccr/xdmod/pull/44),
       [\#71](https://github.com/ubccr/xdmod/pull/71),
       [\#103](https://github.com/ubccr/xdmod/pull/103),
       [\#115](https://github.com/ubccr/xdmod/pull/115),
       [\#123](https://github.com/ubccr/xdmod/pull/123),
       [\#130](https://github.com/ubccr/xdmod/pull/130),
       [\#132](https://github.com/ubccr/xdmod/pull/132),
       [\#135](https://github.com/ubccr/xdmod/pull/135))

2017-01-10 v6.5.0
-----------------

- Features
    - General
        - Modified the Summary tab to reload automatically after its charts are
          modified in Metric Explorer.
        - Modified REST stack to support multiple configuration files, allowing
          modules to supply their own REST resources.
        - Increased maximum size of node lists in database, allowing jobs
          running across more nodes to be more accurately tracked.
        - Improved efficiency of job host list parser and ingestor.
        - Added ability to disable Basic Auth in REST API.
        - Improved email validation.
    - ETLv2
        - Added new PHP-based ETL system to Open XDMoD.
        - Vastly improved logging and debugging.
        - Supports individual ETL actions as well as ordered sets of actions
          called pipelines.
        - Optimized for performance when importing large numbers of records.
        - Predefined actions support flexible ingestion of data from multiple
          sources.
        - ETL is configured via JSON files and requires far less code
          modification when customizing to more easily support flexibility at
          customer installations.
- Bug Fixes
    - General
        - Fixed stacked area charts not handling empty data points correctly.
        - Stopped inaccurate warning about Adobe Flash content in browsers that
          block Flash. (XDMoD does not make use of Flash.)
        - Fixed various typos and grammatical errors.
        - Fixed incorrectly-oriented subpanel titles in Internet Explorer 10.
        - Downgraded PHP packages that required PHP 5.3.9 instead of
          Open XDMoD's current minimum PHP version, 5.3.3.
        - Fixed inconsistency between labels used for users and PIs on a job.
        - Fixed automatic aggregation unit selection for charts not working
          correctly on servers running PHP 5.3.3.
        - Stopped package builds from being logged to an Open XDMoD database.
        - Fixed documentation files from modules overwriting core files when
          installing using tarballs.
        - Fixed UGE shredder.
        - Fixed Slurm job array index parsing.
- Refactors and Miscellaneous
    - Added documentation for using LDAP for federated authentication.
    - Spun the App Kernels, SUPReMM, and XSEDE modules out into separate
      code repositories.
    - Began transition to a more flexible, more efficient data warehouse that
      will support innovative HPC resources including cloud computing resources.
    - Consolidated third-party JavaScript libraries into one library directory.
    - Improved development setup process for external contributors.
    - Migrated website from a single SourceForge site to multiple GitHub sites.
    - Cleaned up and reorganized numerous other assets.
    - Added `reqgres` and `reqtres` to the list of Slurm fields that are
      shredded and stored in the Slurm job table.

2016-09-21 v6.0.0
-----------------

- Important Notes
    - Updated Highcharts from v3.0.9 to v4.2.5.
        - Commercial users (as defined by the Highcharts license terms) will
          need to acquire a new Highcharts license if their current license does
          not cover the new version.
- Features
    - Added demo user type.
    - Added roadmap link to feature request dialog.
    - Shredders
        - Fixed calculation of missing end times from the start time and wall
          time.
        - LSF
            - Now storing the node list, exit code and exit status for SUPReMM
              support.
        - Slurm
            - Now allowing null eligible times.
            - Failed parsing of datetimes are no longer a fatal error.
        - SGE
            - Now supporting older versions of SGE that contain only 43 fields
              in their accounting logs.
- Bug Fixes
    - General
        - Fixed some charts not rendering if more than 1000 points were plotted.
        - Improved consistency of chart aesthetics across tabs.
        - Fixed report generator not cleaning up old report files.
    - Metric Explorer
        - Fixed Add Filter list only including filter types applicable to the
          realm of the first data series on a chart.
    - Shredders
        - PBS
            - Fixed parsing of newer `exec_host` formats (e.g. host/0-3,
              host/0*8).
    - `xdmod-import`
        - Fixed importing of hierarchy and group-to-hierarchy files.
- Refactors and Miscellaneous
    - Refactored Open XDMoD to be the code base upon which XDMoD is built
      instead of the other way around.
    - Updated jQuery from v1.9.1 to v1.12.4.
    - Updated Node.js Mongo driver from v1.4 to v2.1.
    - Improved tab loader to allow tabs to be defined in configuration files
      instead of being hard-coded into the loader.
    - Merged public and private versions of the main page into a single page.
    - Added options to more quickly build Open XDMoD for development purposes.
    - Reduced duplicate code in asset setup script.

2016-05-24 v5.6.0
-----------------

- New Features
    - Federated Authentication
        - Open XDMoD can now use any authentication system supported by
          SimpleSAMLphp (https://simplesamlphp.org/).
        - For more information, please view the
          Federated Authentication guide.
    - Roadmap
        - Added interactive roadmap for XDMoD development to About tab.
    - Contact Us
        - Added option to contact developers for technical support.
    - Data Warehouse
        - Improved speed of filter (dimension value) lists by precomputing
          available values.
    - Metric Explorer
        - Added redo button.
    - Shredders
        - Added support for PBS Pro.
        - Added additional input validation to the Slurm helper script.
    - Upgrade Process
        - Added warning to upgrade addon modules before migrating.
    - Database
        - Changed column types and added indexes to the logging table.
    - Documentation
        - User manual now available.
        - Improved installation and configuration documentation.
- Bug Fixes
    - General
        - Enabled toolbar scrolling when window is too small.
        - Fixed trend line R^2 values sometimes not being calculated.
        - Removed redundant warnings about role-based restrictions.
        - Fixed the About tab not being able to be the default tab.
        - Improved chart loading speed by removing most animations.
        - Fixed incorrect trend line calculation and display for discontinuous
          data series.
        - Fixed security vulnerability in Report Generator's image generator.
        - Fixed extra slashes in URLs in password reset emails (including
          account creation emails).
        - Fixed email template paths.
        - The About tab can now be set as the default module.
    - Data Warehouse
        - Fixed cases where current time period would not be aggregated.
            - Now that this is resolved, code that can work by scanning any
              aggregate table now uses the year tables, as those are smallest.
        - Improved consistency of time period tables and removed unused ones.
    - Metric Explorer
        - Fixed handling of HTML characters in chart titles.
        - Fixed issues with axis renames not persisting.
        - Fixed Load Chart search not working.
        - Modified chart options menu label to be a fixed length.
            - This prevents the button from being placed in the toolbar's
              overflow area when it shouldn't be.
        - Fixed changes to y-axes not being undoable.
        - Modified "Show Trend Line" button in chart options to be disabled when
          trend lines aren't available.
        - Fixed case where duration selectors in main toolbar and on x-axis
          popup could desynchronize.
    - Shredders
        - Fixed handling of "NONE" in SGE's `pe_taskid` field.
        - Now allowing all values that were previously resulting in fatal
          errors in Slurm fields that represent lengths of time.
    - Upgrade Process
        - Fixed broken error for when portal_settings.ini is not writable.
    - Usage
        - Fixed bad default settings for dimensions that default to pie charts.
        - Fixed long initial load time by starting on a single-chart page.

2015-12-18 v5.5.0
-----------------

- New Features
    - SUPReMM support
    - Metric Explorer Changes
        - Improved management of charts
            - Charts are no longer automatically saved after each
              operation. A manual Save button has been added that will
              indicate whether or not a chart needs to be saved.
            - When displaying pie charts, only a single dataset is
              allowed.
            - Added ability to rename saved charts.
            - Added undo capability.
        - Improved UI for data filtering
            - Added Quick Filters for quickly restricting data. This
              replaces the Role selector.
        - Improved data access controls
            - Non-public metrics and dimensions are now restricted
              according to users' roles.
            - Improved filter lists to only include values for which
              the user can view the underlying data.
            - Added the ability to include a remainder data series.
    - Migrated more server calls to the new REST stack
    - Simplified the chart/data retrieval infrastructure used by the
      Metric Explorer and Usage tabs
    - Improved performance of certain queries
    - Updated PBS parsing to support time fields that are formatted in
      seconds and not HH:MM:SS
- Bug Fixes
    - Fixed report generator document formatting
    - Fixed inconsistent data series ordering between timeseries charts
      when sorted by value in Usage and Metric Explorer tabs
    - Fixed Metric Explorer sometimes loading the same chart twice
    - Fixed the "Hide Tooltip" option not always working as expected in
      Usage tab
    - Fixed filter lists occasionally getting stuck under a loading mask
    - Fixed predefined date durations not being saved correctly in the
      Metric Explorer
    - Removed auto-loading of the last open chart in Metric Explorer, a
      new chart is now opened when visiting the Metric Explorer
    - Improved stability, consistency, and performance of numerous other
      components
    - Prevent warning messages produced by LSF jobs that don't have a
      host list

2015-08-19 v5.0.0
-----------------

- New Features
    - Utilization metric may now be normalized to a specified percent
      allocated
    - Removed javac dependency
    - Add host filter shredder feature
    - Improved stability of Usage tab
    - Improved error handling for internal dashboard errors
    - Added meaningful HTTP status codes to failed login responses
    - Resolved frequent warnings in Apache logs about implicitly
      converting arrays to strings
    - PBS memory value parsing errors are no longer fatal
    - Removed Apache requirement for listening on 127.0.0.1
    - Added support for Univa Grid Engine 8.2+
    - Shredders now skip duplicate data
    - Added support for MySQL 5.6+
    - Added support for PhantomJS 2.0+
    - Added helper script for updating resource specs
    - Improved documentation
- Bug Fixes
    - Adjust LSF unique key constraint
    - Added PBS unique key constraint to prevent duplicate job data

2015-02-17 v4.5.2
-----------------

- New Features
    - Remove excessive "wall time" error messages
    - Add more debug logging
    - SGE memory value parsing errors are no longer fatal
- Bug Fixes
    - Fix SGE parsing regression

2015-02-05 v4.5.1
-----------------

- New Features
    - Support for Torque's new "exec_host" syntax
    - Updated SGE parsing to support Univa's new "job_class" field and
      requests for "INFINITY" memory
- Bug Fixes
    - Backslash characters in LSF log files now parsed correctly
    - Report generator now handles double quotes in portal_settings.ini
      properly
    - Fixed optional Node Utilization statistic

2014-12-04 v4.5.0
-----------------

- New Features
    - Major re-write of the Metric Explorer user interface including a
      metric catalog and context-sensitive menus
    - Report generator performance enhancements
    - General user interface updates
    - Improved image export dialog
    - Added metric description pane in Metric Explorer
    - Improved session management
    - Added additional error checking and reporting to shredders
    - Added LSF support
    - Improved utilization metric to support resources that don't have a
      constant cpu count over their lifetime
    - Added suport for alternate PI data sources
    - Improved logging
    - Added weighted job size statistic
    - Improved IE compatibility
    - Added xdmod-admin script
    - Added automatic update checker
    - Added support for slurm job arrays
    - Improved documentation
- Bug Fixes
    - PBS and SGE job arrays were not ingested correctly
    - sacct configuration option was not recognized
    - Arrow keys did not work in axis range boxes
    - Parentheses not available in Metric Explorer chart titles
    - Proper handling of apostrophes in search dialogs
    - On some charts, data points on the last day of the specified date
      range were not displayed correctly

2013-11-18 v3.5.0
-----------------

- Initial public release
