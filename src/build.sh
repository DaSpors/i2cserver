#!/bin/bash

ARCH=$(uname -m)
gcc -o bin/i2ctool_$ARCH i2ctool.c
