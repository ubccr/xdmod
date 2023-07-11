import sys
import os

sys.path.insert(0, os.path.abspath('_ext'))

from custom_roles import only_text_role, only_numref_role, only_role

# Configuration file for the Sphinx documentation builder.
#
# For the full list of built-in configuration values, see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Project information -----------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#project-information

project = 'XDMoD Manual'
copyright = '2023, UB CCR'
author = 'UB CCR'

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = ['sphinx_rtd_theme']

templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

numfig = True


# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

html_theme = 'sphinx_rtd_theme'
html_static_path = ['_static']
html_logo = "https://lh6.googleusercontent.com/e5KIW2f4HAY96PH8Oob-LPHSHpChwqPk9GG0C7UgRHdhqm8xwC8W2kLyyj4pln4THP5V-xkM8BI-bbO9yutuXjbjP9tki1UpGRxqP9RdwUCq6JZD7BHrBI4YLCOVXYiC3pHb5AZXUOH-I70"
html_theme_options = {
    'logo_only': True,
}

def setup(app):
    app.add_role('only-text', only_text_role)
    app.add_role('only-numref', only_numref_role)
    app.add_role('only', only_role)
    app.add_css_file('custom.css')
    
