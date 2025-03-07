#!/bin/bash

BUILDDIR=user_manual_builder
BUILDENV=$BUILDDIR/sphinx_venv

rm -rf $BUILDENV

python3 -m venv $BUILDENV
source $BUILDENV/bin/activate

pip3 install --upgrade pip
pip3 install -r $BUILDDIR/requirements.txt
