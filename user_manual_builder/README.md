# Create the XDMoD User Manual

## Build Scripts

To create the XDMoD User Manual, simply run the setup_user_manual script, which will handle
installing dependencies in a python venv and building the manual.

```shell
setup_user_manual.sh
-b|--builddir : Directory where manuals are built from
-d|--destdir : Directory where the built manual is stored
-v|--version : Manual version to be linked (Open or XSEDE)
```

The setup_user_manual script also calls the build_user_manual script, which is responsible
for formatting and building the script.

```shell
build_user_manual.sh
-b|--builddir : Directory where manuals are built from
-d|--destdir : Directory where the built manual is stored
-v|--version : Manual version to be linked (Open or XSEDE)
```

## Sphinx Guide

### reStructuredText Guide

reStructuredText is a lightweight markup language, developed by the Python community,
that aims to offer more advanced formatting and extensibility compared to Markdown.

#### Syntax Example
```rst
===========
Heading 1
===========

Heading 2
---------

Heading 3
~~~~~~~~~

- Bullet point 1
- Bullet point 2

1. Numbered item 1
2. Numbered item 2

**Bold text**
*Italic text*
`Link <https://www.example.com>`_
```

Sphinx uses the headings to automatically generate chapters and subchapters, depending
on the depth of the heading.

#### Roles and Directives

##### Roles

Roles are used for inline markup:

```rst
:emphasis:`Emphasized text: Renders as Emphasized text.
:strong:`Strong text: Renders as Strong text.
:code:`code_example(): Renders as code_example().
```

##### Directives

Directives are block-level elements:

```rst
.. note:: Renders a highlighted note or tip box.
.. image:: Embeds an image with specified attributes.
.. code-block:: Displays a code block with syntax highlighting.
.. figure:: path/to/image
```

#### Figure Directive

Directives can also have extra options to enhance usability. For each figure in the manual,
it has an associated name and caption to allow it to be referenced by a numref role or only role

Example:
```rst
.. figure:: path/to/image
   :name: name_of_image

   caption text
```
will then get rendered as

Figure image

Fig. # caption text

This can then be referenced by a numref in the form ":numref:`name_of_image`". This will generate
the text "fig. #"

Note: For automatic figure numbering to be enabled, "numfig = True" must be in conf.py

#### Only Directive

Another directive that is commonly used in the manual is the only directive. This is used to
automatically separate between the two versions

Example:
```rst
.. only:: tag_name

   text
```

#### only.py role extension

In this project, there is a custom extension called only, that is a combination of the only
directive and the functionality of roles.

Use in reStructuredText:
```rst
:only:`<tag-name>text{figure or table reference name}additional text if needed`
```
Breakdown:
<> : tag name in angled brackets

{} : reference name in curly brackets

text : not wrapped by anything

The extension code uses the app variable stored by Sphinx and the .has(tag) function to separate XSEDE and Open
versions of XDMoD. The only function then relies on Sphinx's node based system to add in the proper nodes
depending on what tags are active. This shouldn't require a lot of maintenance, as the extension works for 4
previous versions. If the extension stops working, the most likely culprit is the app variable and how it is
declared.

### Breaking down the sphinx-build command

This is the bread and butter of build_user_manual.sh. This uses Sphinx to build the user manual and store
it in the intended directory.
```shell
sphinx-build -E -t $MANUAL_VERSION $BASE_BUILD_DIR $DEST_DIR
```
-E : hard rebuild of Sphinx. If not present, can cause issues when changing a specific file

-t $MANUAL_VERSION : name of tag or version. Manual is currently only setup for XSEDE and Open

$BASE_BUILD_DIR : source files

$DEST_DIR : destination files
