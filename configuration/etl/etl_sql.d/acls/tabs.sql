INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'tg_summary'                   AS name,
              'Summary'                      AS display,
              100                            AS position,
              TRUE                           AS is_default,
              'XDMoD.Module.Summary'         AS javascript_class,
              'CCR.xdmod.ui.tgSummaryViewer' AS javascript_reference,
              'Displays summary information' AS tooltip,
              'Summary Tab'                  AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'tg_usage'                        AS name,
              'Usage'                           AS display,
              200                               AS position,
              FALSE                             AS is_default,
              'XDMoD.Module.Usage'              AS javascript_class,
              'CCR.xdmod.ui.chartViewerTGUsage' AS javascript_reference,
              'Displays usage'                  AS tooltip,
              'Usage Tab'                       AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'metric_explorer'             AS name,
              'Metric Explorer'             AS display,
              300                           AS position,
              FALSE                         AS is_default,
              'XDMoD.Module.MetricExplorer' AS javascript_class,
              'CCR.xdmod.ui.metricExplorer' AS javascript_reference,
              ''                            AS tooltip,
              'Metric Explorer'             AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'report_generator'             AS name,
              'Report Generator'             AS display,
              1000                           AS position,
              FALSE                          AS is_default,
              'XDMoD.Module.ReportGenerator' AS javascript_class,
              'CCR.xdmod.ui.reportGenerator' AS javascript_reference,
              ''                             AS tooltip,
              'Report Generator'             AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'about_xdmod'          AS name,
              'About'                AS display,
              10000                  AS position,
              FALSE                  AS is_default,
              'XDMoD.Module.About'   AS javascript_class,
              'CCR.xdmod.ui.aboutXD' AS javascript_reference,
              ''                     AS tooltip,
              'About'                AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'job_viewer'                      AS name,
              'Job Viewer'                      AS display,
              5000                              AS position,
              FALSE                             AS is_default,
              'XDMoD.Module.JobViewer'          AS javascript_class,
              'CCR.xdmod.ui.jobViewer'          AS javascript_reference,
              'View detailed job-level metrics' AS tooltip,
              'Job Viewer'                      AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'app_kernels'                                                                AS name,
              'App Kernels'                                                                AS display,
              400                                                                          AS position,
              FALSE                                                                        AS is_default,
              'XDMoD.Module.AppKernels'                                                    AS javascript_class,
              'CCR.xdmod.ui.appKernels'                                                    AS javascript_reference,
              'Displays data reflecting the reliability and performance of grid resources' AS tooltip,
              'App Kernels'                                                                AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;
--
INSERT INTO tabs (module_id, parent_tab_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id         AS module_id,
              t.tab_id            AS parent_tab_id,
              'app_kernel_viewer' AS name,
              'App Kernel Viewer' AS display,
              100                 AS position,
              FALSE               AS is_default,
              ''                  AS javascript_class,
              ''                  AS javascript_reference,
              ''                  AS tooltip,
              ''                  AS user_manual_section_name
          FROM modules m, tabs t
          WHERE m.name = 'xdmod'
                AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, parent_tab_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id           AS module_id,
              t.tab_id              AS parent_tab_id,
              'app_kernel_explorer' AS name,
              'App Kernel Explorer' AS display,
              200                   AS position,
              FALSE                 AS is_default,
              ''                    AS javascript_class,
              ''                    AS javascript_reference,
              ''                    AS tooltip,
              ''                    AS user_manual_section_name
          FROM modules m, tabs t
          WHERE m.name = 'xdmod'
                AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, parent_tab_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id               AS module_id,
              t.tab_id                  AS parent_tab_id,
              'app_kernel_notification' AS name,
              'App Kernel Explorer'     AS display,
              300                       AS position,
              FALSE                     AS is_default,
              ''                        AS javascript_class,
              ''                        AS javascript_reference,
              ''                        AS tooltip,
              ''                        AS user_manual_section_name
          FROM modules m, tabs t
          WHERE m.name = 'xdmod'
                AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'my_allocations'                 AS name,
              'Allocations'                    AS display,
              350                              AS position,
              FALSE                            AS is_default,
              'XDMoD.Module.Allocations'       AS javascript_class,
              'CCR.xdmod.ui.AllocationViewer'  AS javascript_reference,
              'Displays your allocation usage' AS tooltip,
              'Allocations Tab'                AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;


INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'compliance'                 AS name,
              'Compliance'                 AS display,
              2000                         AS position,
              FALSE                        AS is_default,
              'XDMoD.Module.Compliance'    AS javascript_class,
              'CCR.xdmod.ui.complianceTab' AS javascript_reference,
              ''                           AS tooltip,
              'Compliance Tab'             AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'custom_query'               AS name,
              'Custom Queries'             AS display,
              3000                         AS position,
              FALSE                        AS is_default,
              'XDMoD.Module.CustomQueries' AS javascript_class,
              'CCR.xdmod.ui.customQuery'   AS javascript_reference,
              ''                           AS tooltip,
              'Custom Queries Tab'         AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;

INSERT INTO tabs (module_id, name, display, position, is_default, javascript_class, javascript_reference, tooltip, user_manual_section_name)
    SELECT inc.*
    FROM (SELECT
              m.module_id,
              'sci_impact'                                           AS name,
              'Sci Impact'                                           AS display,
              4000                                                   AS position,
              FALSE                                                  AS is_default,
              'XDMoD.Module.SciImpact'                               AS javascript_class,
              'CCR.xdmod.ui.impact'                                  AS javascript_reference,
              'Scientific Impact by user, organization, and project' AS tooltip,
              'Sci Impact Tab'                                       AS user_manual_section_name
          FROM modules m
          WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN tabs cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.position = inc.position
               AND cur.is_default = inc.is_default
               AND cur.javascript_class = inc.javascript_class
               AND cur.javascript_reference = inc.javascript_reference
               AND cur.tooltip = inc.tooltip
               AND cur.user_manual_section_name = inc.user_manual_section_name
    WHERE cur.tab_id IS NULL;
