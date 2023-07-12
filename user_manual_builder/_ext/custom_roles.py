from docutils import nodes
from docutils.parsers.rst import roles
from sphinx.application import Sphinx
from sphinx.util.nodes import make_refnode
from sphinx.util.nodes import split_explicit_title
from docutils.parsers.rst.roles import set_classes
from sphinx import addnodes

def extract_tag(text: str):
    """
    text: string, an unmanipulated string from a role
    returns: string, string tuple. first string is the extracted tag, and the second string is the rest
             of the text without the tag.
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

def only_text_role(name: str, rawtext: str, text: str, lineno: int, inliner, options={}, content=[]):
    """
    name: string
    rawtext: string inclduing the name of role
    text: string inside of the back ticks
    lineno: int
    inliner
    options: dictionary
    content: dictionary
    returns: inline node that only contains text, without the tag name
    usage: :only-text: `<tag-name>desired text`
            tag name is extracted out of the text with extract_tag(text) function, and then returns the text node
            if the extracted tag is active.
    """

    env = inliner.document.settings.env
    tags = env.app.tags
    
    tag, text = extract_tag(text)

    if tag not in tags:
        return [], []
    else:
        node = nodes.inline(rawtext, text, **options)
        return [node], []

def only_numref_role(name: str, rawtext: str, text: str, lineno: int, inliner, options={}, content=[]):
    """
    name: string
    rawtext: string inclduing the name of role
    text: string inside of the back ticks
    lineno: int
    inliner
    options: dictionary
    content: dictionary
    returns: pending_xref node that contains the reference if it exists, and an inline child node if reference does not exist
             also removes the tag name from the entered text
    usage: :only-numref:`<tag-name>name of crossreference`
            tag name is extracted out of the text with extract_tag(text) function, and then returns the numref node
            if the extracted tag is active.
    """

    env = inliner.document.settings.env
    tags = env.app.tags

    tag, text = extract_tag(text)

    if tag not in tags:
        return [], []
    else:
        set_classes(options)
        node = addnodes.pending_xref(
            '',
            refdomain='std',
            reftype='numref',
            reftarget=text,
            refexplicit=False,
            **options
        )
        node += nodes.inline(text, text, classes=['xref', 'std', 'std-numref'])
        return [node], []

def only_role(name: str, rawtext: str, text: str, lineno: int, inliner, options={}, content=[]):
    """
    name: string
    rawtext: string inclduing the name of role
    text: string inside of the back ticks
    lineno: int
    inliner
    options: dictionary
    content: dictionary
    returns: inline node that contains the any order of text and reference nodes
             also removes the tag name from the entered text
    usage: :only:`<tag-name>desired text {name of crossref} additional desired text`
            tag name is extracted out of the text with extract_tag(text) function, and then returns a list of nodes, making sure to
            differentiate between reference nodes and text nodes.
    """

    env = inliner.document.settings.env
    tags = env.app.tags

    tag, text = extract_tag(text)

    if tag not in tags:
        return [], []
    else:
        curr = ''
        is_ref = False
        node = nodes.inline(rawtext, '', **options)
        for c in text:
            if c == '{':
                is_ref = True
                node += nodes.inline(rawtext, curr, **options)
                curr = ''
            elif c == '}':
                is_ref = False
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

def setup(app: Sphinx):
    app.add_role('only-text', only_text_role)
    app.add_role('only-numref', only_numref_role)
    app.add_role('only', only_role)
    return {'version': '0.1', 
            'parallel_read_safe': True, 
            'parallel_write_safe': True,}

