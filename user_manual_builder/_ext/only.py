"""Creates the only role extension for Sphinx
"""
from docutils import nodes
from docutils.parsers.rst.roles import set_classes

from sphinx import addnodes
from sphinx.application import Sphinx

def extract_tag(text):
    """
    Extracts out a tag string from string in format `<tag-name>rest of text`
    """
    tag = ''
    tag_start = tag_end = 0
    on_tag = False
    for i, c in enumerate(text):
        if c == '<':
            on_tag = True
            tag_start = i
        elif c == '>':
            tag_end = i+1
            break
        elif on_tag == True:
            tag += c
    text = text[:tag_start] + text[tag_end:]
    return tag, text

def only_role(name, rawtext, text, lineno, inliner, options={}, content=None):
    """
    Creates a Sphinx extension that can filter out text based on the entered tags
    Implemented because Sphinx only has a block directive of filtering out text, and not inline, so this satisfies that
    Usage: :only:`<tag-name>desired text {name of crossref} additional desired text`
            tag name is extracted out of the text with extract_tag(text) function, and then returns a list of nodes, making sure to
            differentiate between reference nodes and text nodes.
    """

    env = inliner.document.settings.env
    tags = env.app.tags

    tag, text = extract_tag(text)

    if not tags.has(tag):
        return [], []
    curr = ''
    node = nodes.inline(rawtext, '', **options)
    for c in text:
        if c == '{':
            node += nodes.inline(rawtext, curr, **options)
            curr = ''
        elif c == '}':
            set_classes(options)
            pend_node = addnodes.pending_xref(
                '',
                refdomain='std',
                reftype='numref',
                reftarget=curr,
                refexplicit=False,
                **options
            )
            pend_node += nodes.inline(rawtext, curr, classes=['xref', 'std', 'std-numref'])
            node += pend_node
            curr = ''
        else:
            curr += c
    if curr != '':
        node += nodes.inline(rawtext, curr, **options)
    return [node], []

def setup(app):
    """Install the plugin.

    :param app: Sphinx application context.
    """
    app.add_role('only', only_role)
    return {'version': '0.1',
            'parallel_read_safe': True,
            'parallel_write_safe': True,}
