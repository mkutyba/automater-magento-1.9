#!/usr/bin/env bash

composer install
zip -r automater-pl.zip app lib composer.json composer.lock modman README.md
