from docutils import nodes
from docutils.parsers.rst import roles
from sphinx.application import Sphinx
from sphinx.util.nodes import make_refnode
from sphinx.util.nodes import split_explicit_title
from docutils.parsers.rst.roles import set_classes
from sphinx import addnodes

def extract_tag(text: str):
    # extract tag from inline text string
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
    # usage: :only-text:`<tag-name>desired text`

    env = inliner.document.settings.env
    tags = env.app.tags
    
    tag, text = extract_tag(text)

    if tag not in tags:
        return [], []
    else:
        node = nodes.inline(rawtext, text, **options)
        # print('text role node', node)
        return [node], []

def only_numref_role(name: str, rawtext: str, text: str, lineno: int, inliner, options={}, content=[]):
    # usage: :only-numref:`<tag-name>name of crossreference`

    env = inliner.document.settings.env
    tags = env.app.tags

    tag, text = extract_tag(text)

    if tag not in tags:
        # The tag is not enabled, so return an empty node.
        return [], []
    else:
        # The tag is enabled, so return the node with the content.
        set_classes(options)
        node = addnodes.pending_xref(
            '',
            refdomain='std',
            reftype='numref',
            reftarget=text,
            refexplicit=False,
            **options
        )
        # print('numref role first node', node)
        node += nodes.inline(text, text, classes=['xref', 'std', 'std-numref'])
        # print('numref role fin node', node)
        return [node], []

def only_role(name: str, rawtext: str, text: str, lineno: int, inliner, options={}, content=[]):
    # usage: :only:`<tag-name>desired text {name of crossref} additional desired text`
    #        can use any order combination of text and numrefs

    env = inliner.document.settings.env
    tags = env.app.tags

    # extract tag and separate text
    tag, text = extract_tag(text)

    if tag not in tags:
        # The tag is not enabled, so return an empty node.
        return [], []
    else:
        # The tag is enabled, so return the node with the content.
        curr = ''
        is_ref = False
        node = nodes.inline(rawtext, '', **options)
        #node = nodes.inline(rawtext, curr, **options)
        for i, c in enumerate(text):
            if c == '{':
                is_ref = True
                #if not node:
                #    node = nodes.inline(rawtext, curr, **options)
                #else:
                node += nodes.inline(rawtext, curr, **options)
                #nodeList.append(nodes.inline(rawtext, curr, **options))
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
                '''
                nodeList.append(addnodes.pending_xref(
                    '',
                    refdomain='std',
                    reftype='numref',
                    reftarget=curr,
                    refexplicit=True,
                    **options
                ))
                #print(text, node)
                nodeList.append(nodes.inline(rawtext, curr, classes=['xref', 'std', 'std-numref']))
                curr = ''
                '''
            else:
                curr += c
        if curr != '':
            node += nodes.inline(rawtext, curr, **options)
            #nodeList.append(nodes.inline(rawtext, curr, **options))
        #para = nodes.paragraph()
        #print('para', para)
        #_ = [para.append(node) for node in nodeList]
        #print('para mod', para)
        # print('final nodes', node)
        return [node], []

def setup(app: Sphinx):
    app.add_role('only-text', only_text_role)
    app.add_role('only-numref', only_numref_role)
    app.add_role('only', only_role)
    return {'version': '0.1', 
            'parallel_read_safe': True, 
            'parallel_write_safe': True,}

