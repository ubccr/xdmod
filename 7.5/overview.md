---
title: Overview
---

Data Flow
---------
1. Data from log files is parsed and inserted into the database
   (mod_shredder).
2. Data is normalized.
3. Data is inserted into the HPcDB (mod_hpcdb).
4. Data is denormalized (modw).
5. Data is aggregated (modw_aggregates).
