# Acl Table Overview
What follows is a general overview of what tables have been created to support 
the modular Acl framework. This will include what information they are meant to 
contain, what purpose they fulfill, as well as how they relate to other tables in
the system. 

## Modules (modules)
As one of the major focuses of this feature was to support a more 'modular' 
approach to XDMoD development. It made sense to have a place to track which 
modules are installed at any given point in time. It also provides a mechanism 
(a relation to module_versions, current_version_id) that allows each module to 
update its version independantly of one another.
   
## Module Versions (module_versions)
Module Versions provides a location to track historical version information for
each module currently installed in the system. 

## Realms (realms)
Realms provides a location for modules to indicate which realms they are 
providing to the system. They are meant to represent a 'fact' (Datawarehouseing term)
and keep track of which schema / table they are associated with. A realm is also
used as one of the main grouping points for other related information such as Group Bys and Statistics 

## Hierarchies (hierarchies)
Another one of the requested features was to make the ordering of various pieces
of information not hard coded ( i.e. are previously hard coded 3 level ???). To 
that end a module may indicate that it is providing a custom hierarchy which can be
used in one of the pre-existing \<table>_hierarchies tables or in a new hierarchy table. 
By having a hierarchy with a reference to a module we can also support two modules
having different hierarchies for the same table.

## Acl Types (acl_types)
This table just describes what types of acls the system currently supports. It also
allows us to treat different categories of acls in different ways based on it's type.

## Acls (acls)
This table tracks which acls the system currently supports in addition to which module 
they are associated with and whether or not they are enabled. Functionally they
provide a way to control access to functionality and associate it with, among 
other things, groups of users. 

## Group Bys (group_bys)
This table tracks which group bys the system currently supports. Group Bys are 
used by the underlying datawarehouse query system to determine how and by what
a particular realm can be grouped and or filtered.

## Statistics (statistics)
This table tracks which statistics the system currently supports. Statistics 
are used by the underlying datawarehouse system to determine the final values 
returned from a query. They can be thought of as SELECT clauses in a SQL statement.
 
## Tabs (tabs)
This table tracks which tabs, as in user interface tabs, are available for 
the system and its users. By using this table, acl_tabs and user_acls we can arrive
at which tabs a user should be seeing based on their assigned acls.

## Acl Hierarchies (acl_hierarchies)
This table is for tracking which, if any, acls participate in a given hierarchy.
The 'level' column provides for an explicit ordering of the associated acls.  

## User Acls (user_acls)
It is within this table that a users relation to a set of acls is captured and as such 
the main authorization method occurs. 

## Acl Group Bys (acl_group_bys)
This table tracks which acls have access to which group bys. 

## Acl Tabs (acl_tabs)
This table tracks which acls have access to which tabs.
