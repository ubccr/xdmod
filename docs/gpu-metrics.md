---
title: GPU Metrics
---

Open XDMoD includes support for GPU metrics in the jobs realm starting with
version 9.0.0.  Specifically, the number of GPUs allocated to each job is
tracked and used to calculate the number of GPU hours and to allow grouping by
the number of GPUs allocated.

Only Slurm, PBS and Grid Engine are supported at this time.

**Please note that if your resource manager is not supported or GPU data is not
available/parsable, that Open XDMoD will report zero GPU hours and a GPU count
of zero.**

## Slurm

The GPU count source for Slurm data is the [`AllocTRES` accounting field taken
from `sacct` output][slurm-sacct-alloctres].  This field contains the
[Trackable Resources][slurm-tres] allocated to the job.  The specific resource
that is used to determine the GPU count is the [Generic Resource][slurm-gres]
identified by `gres/gpu`.

For example:

```
billing=10,cpu=10,gres/gpu=4,mem=374000M,node=2
```

This `AllocTRES` value would indicate that the job was allocated 4 GPUs.

## PBS

The GPU count source for PBS data is the `Resource_List.nodes` field in the
accounting log files.  If a value is specified for `gpus` then that is used as
the number of GPUs per node.

For example:

```
Resource_List.nodes=2:ppn=32:gpus=2
```

This would indicate that the job used 4 GPUs (2 GPUs per node * 2 nodes).

Other versions of PBS use `Resource_List.ngpus`:

```
Resource_List.ngpus=4
```

This would indicate that the job used 4 GPUs.

### Non-Standard PBS GPU data

In addition to the standard way of logging GPUs, two non-standard ways of
logging this data are also supported.  If the GPU count cannot be determined
from `Resource_List.nodes` then these will be used when present.

If the `Resource_List.nodect` field specifies a value for `gpus` then that will
be used.

For example:

```
Resource_List.nodect=2:ppn=32:gpu=2
```

This would indicate that the job used 4 GPUs (2 GPUs per node * 2 nodes).

If neither of those methods produce a value, then `Resource_List.gpu` may be
used to determine the number of GPUs.

For example:

```
Resource_List.gpu=2
```

This would indicate that the job used 2 GPUs.

## Grid Engine (UGE)

Univa Grid Engine is the only Grid Engine based product that is confirmed to
report GPU count data.  If your Grid Engine based product reports GPU data in
the same format then it will be interpreted in the same was as described here.

Please report any issues to the email address on our [support page](support.html).

The GPU count source for UGE is the `category` field in the accounting log
files.  If a value is specified for `gpu` then that is used as the total number
of GPUs for the job.

For example:

```
-l gpu=1
```

This would indicate that the job used 1 GPU.

Grid Engine accounting logs contain one line per node.  If conflicting GPU
counts are found in the data for a job then the greatest value will be used for
the GPU count.

[slurm-sacct-alloctres]: https://slurm.schedmd.com/sacct.html#OPT_AllocTres
[slurm-tres]: https://slurm.schedmd.com/tres.html
[slurm-gres]: https://slurm.schedmd.com/gres.html
