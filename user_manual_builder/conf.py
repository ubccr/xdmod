import sys
import os
sys.path.append(os.path.abspath('./_ext'))

# Configuration file for the Sphinx documentation builder.
#
# For the full list of built-in configuration values, see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Project information -----------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#project-information

project = 'XDMoD Manual'
copyright = ' University at Buffalo Center for Computational Research'
author = 'UB CCR'
release = ''

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = ['sphinx_rtd_theme', 'only']

templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']
if 'Open' in tags:
    exclude_patterns.extend(['Compliance_Tab.rst', 'Allocations_Tab.rst'])

numfig = True


# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

html_theme = 'sphinx_rtd_theme'
html_static_path = ['_static']
html_logo = 'media/xdmod_logo.png'
html_theme_options = {
    'logo_only': True,
    'collapse_navigation': False,
    'style_nav_header_background': '#e0e0e0',
}
html_show_sourcelink = False

def setup(app):
    app.add_css_file('custom.css')
