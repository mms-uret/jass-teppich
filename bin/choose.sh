#!/usr/bin/env bash

while true; do
    bin/console jass:choose jass1.csv
    printf `cat jass1.csv | wc -l`\\r
done