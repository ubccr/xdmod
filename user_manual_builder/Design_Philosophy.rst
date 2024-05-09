Design Philosophy
====================

The XDMoD portal provides end users with a rich web-based user interface
as shown in **Figure 2-1**. Developed using the
`Model-View-Controller <http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller>`__
design pattern, the view is provided via a thin client written entirely
in JavaScript and built on the ExtJS user interface toolkit while the
Controllers and access to the Model are provided by a set of web-based
RESTful services. Information in the portal is organized using a set of
tabs that allows related information to be grouped together while
providing the user an easy way to navigate the interface and make the
most of the available screen real estate. The RESTful services provide
access to data stored in the warehouse while maintaining a clear
separation between the user interface and the application logic. This
also allows 3rd party tools to access the services directly and ingest
or present the information as they see fit.
