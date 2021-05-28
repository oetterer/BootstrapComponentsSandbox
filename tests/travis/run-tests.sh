#!/bin/bash
set -ex

originalDirectory=$(pwd)
cd ..
baseDir=$(pwd)
mwDir=mw

echo "originalDirectory: $originalDirectory"
echo "mwDir: $mwDir"
echo "TYPE: $TYPE"

cd ${baseDir}/${mwDir}/extensions/BootstrapComponents

if [ "$TYPE" == "coverage" ]; then
	composer unit -- --coverage-clover ${originalDirectory}/build/coverage.clover
elif [ "$TYPE" == "integration" ]; then
	composer integration
else	# "$TYPE" == "unit" or undefined
	composer unit
fi

echo "done testing"
