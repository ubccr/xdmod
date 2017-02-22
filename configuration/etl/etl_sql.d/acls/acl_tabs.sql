-- mgr acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'mgr'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND a.name = 'mgr'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'mgr'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'mgr'
                   AND t.name = 'app_kernel_notification'
                   AND tpt.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- mgr acl tabs 10

-- cd acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cd'
                   AND t.name = 'compliance'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cd'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cd'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cd'
                   AND t.name = 'app_kernel_notification'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- cd acl tabs 11

-- cs acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cs'
                   AND t.name = 'compliance'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cs'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cs'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cs'
                   AND t.name = 'app_kernel_notification'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- cs acl tabs 11

-- po acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'compliance'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'custom_query'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'po'
                   AND t.name = 'sci_impact'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'po'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'po'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'po'
                   AND t.name = 'app_kernel_notification'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- po acl tabs 13

-- usr acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'usr'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'usr'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'usr'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- usr acl tabs 9

-- pi acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pi'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'pi'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'pi'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- pi acl tabs 9

-- cc acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'metric_explorer'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'my_allocations'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'app_kernels'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'report_generator'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'cc'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cc'
                   AND t.name = 'app_kernel_viewer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, parent_acl_tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id      AS acl_id,
                 t.tab_id      AS tab_id,
                 pt.acl_tab_id AS acl_parent_tab_id,
                 NULL          AS position,
                 NULL          AS is_default
             FROM acls a, tabs t, acl_tabs pt, tabs tpt
             WHERE pt.tab_id = tpt.tab_id
                   AND pt.acl_id = a.acl_id
                   AND a.name = 'cc'
                   AND t.name = 'app_kernel_explorer'
                   AND tpt.name = 'app_kernels'

         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- cc acl tabs

-- pub acl tabs
INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pub'
                   AND t.name = 'tg_summary'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pub'
                   AND t.name = 'tg_usage'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;

INSERT INTO acl_tabs (acl_id, tab_id, position, is_default)
    SELECT inc.*
    FROM (
             SELECT
                 a.acl_id,
                 t.tab_id,
                 NULL AS position,
                 NULL AS is_default
             FROM acls a, tabs t
             WHERE a.name = 'pub'
                   AND t.name = 'about_xdmod'
         ) inc
        LEFT JOIN acl_tabs cur
            ON cur.acl_id = inc.acl_id
               AND cur.tab_id = inc.tab_id
    WHERE cur.acl_tab_id IS NULL;
-- pub acl tabs
