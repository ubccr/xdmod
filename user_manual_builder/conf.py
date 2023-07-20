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
copyright = '2023, UB CCR'
author = 'UB CCR'
release = '11.0'

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = ['sphinx_rtd_theme', 'only']

templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

numfig = True


# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

html_theme = 'sphinx_rtd_theme'
html_static_path = ['_static']
html_logo = 'media/image43.png'
html_theme_options = {
    'logo_only': True,
    'collapse_navigation': False,
    'style_nav_header_background': '#e0e0e0',
}
html_context = {
    'display_github': True,
    'github_user': 'ubccr',
    'github_repo': 'xdmod',
}

def setup(app):
    app.add_css_file('custom.css')