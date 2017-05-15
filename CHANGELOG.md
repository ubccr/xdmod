Open XDMoD Change Log
=====================

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
