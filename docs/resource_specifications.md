---
title: Resource Specifications Realm
---

## Prerequisites
- A full working installation of XDMoD. [XDMoD install instructions](install.html)

## What is the Resource Specifications realm?
The Resource Specifications realm in Open XDMoD provides a way to track the changes in computing capacity over time, such as the number of CPUs and GPUs as well as CPU Hours, GPU Hours and other metrics. The source for these data is the `resource_specs.json` configuration file (see the [Configuration Guide](configuration.md)). The data
from this file are ingested into the XDMoD database when `xdmod-ingestor` is run. The only extra command needed is to aggregate the data using the `xdmod-ingestor` command. Please see the [`xdmod-ingestor` guide](ingestor.md) guide for further information.

## Available metrics
- Average Number of CPU Cores: Allocated (Core Count)
  - The average number of allocated CPU cores available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes or outages of resources.
- Average Number of CPU Cores: Total (Core Count)
  - The average number of CPU cores available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes, outages of resources, or the percent of a resource allocated.
- Average Number of CPU Nodes: Allocated
  - The average number of allocated CPU nodes available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes or outages of resources.
- Average Number of CPU Nodes: Total
  - The average number of CPU nodes per day during the days in which the resource(s) were operational during selected time period. This does not take into account downtimes, outages of resources, or the percent of a resource allocated.
- Average Number of GPU Nodes: Allocated
  - The average number of allocated GPU nodes available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes or outages of resources.
- Average Number of GPU Nodes: Total
  - The average number of GPU nodes available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes, outages of resources, or the percent of a resource allocated.
- Average Number of GPUs: Allocated (GPU Count)
  - The average number of allocated GPUs available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes or outages of resources.
- Average Number of GPUs: Total (GPU Count)
  - The average number of GPUs available per day during the days in which the resource(s) were operational during the selected time period. This does not take into account downtimes, outages of resources, or the percent of a resource allocated.
- CPU Hours: Allocated
  - The number of CPU hours available to be allocated during a time period.
- CPU Hours: Total
  - The total number of CPU hours for CPU resources during a time period. The percent of the resource allocated is not taken into account for this statistic.
- CPU Node Hours: Allocated
  - The number of CPU node hours available to be allocated during a time period.
- CPU Node Hours: Total
  - The total number of CPU node hours for CPU resources during a time period. The percent of the resource allocated is not taken into account for this statistic
- GPU Hours: Allocated
  - The number of GPU hours available to be allocated during a time period.
- GPU Hours: Total
  - The total number of GPU hours for GPU resources during a time period. The percent of the resource allocated is not taken into account for this statistic.
- GPU Node Hours: Allocated
  - The number of GPU node hours available to be allocated during a time period.
- GPU Node Hours: Total
  - The total number of GPU node hours for GPU resources during a time period. The percent of the resource allocated is not taken into account for this statistic.

## Dimensions available for grouping
- Resource
  - A resource is defined as any compute infrastructure.
- Resource Type
  - A categorization of resources into by their general capabilities.
- Resource Allocation Type
  - The resource allocation type is how the resource is allocated to users, such as CPU, Node, GPU, etc.
- Service Provider
  - A service provider is an institution that hosts resources.
