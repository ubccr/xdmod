---
title: Univa Grid Engine (UGE) Notes
---

Open XDMoD includes support for Univa Grid Engine starting with version
5.0.0.  Specifically, the Open XDMoD `uge` shredder option expects your
accounting log files to have higher precision (millisecond) timestamps.
All the other notes for [Grid Engine](resource-manager-sge.html) still
apply to Univa Grid Engine.

If your version of Univa Grid Engine uses the same accounting log format
as Sun Grid Engine (no millisecond timestamps), you should use the
[Grid Engine](resource-manager-sge.html) shredder.

Differences from Sun Grid Engine
--------------------------------

Some versions of Univa Grid Engine create accounting log files that are
different from other versions of Sun Grid Engine and it's other
derivatives.  These accounting logs may include additional fields.
Univa Grid Engine (8.2+) accounting log files record timestamps with
millisecond precision.  Open XDMoD ignores the additional fields and
rounds the timestamps down to the nearest second.
