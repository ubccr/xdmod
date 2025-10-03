#!/bin/bash

BUILDDIR=user_manual_builder
BUILDENV=$BUILDDIR/sphinx_venv

rm -rf $BUILDENV

dnf install -y python3.11
python3.11 -m venv "$BUILDENV"
source $BUILDENV/bin/activate

pip3 install --upgrade pip
pip3 install -r $BUILDDIR/requirements.txt
