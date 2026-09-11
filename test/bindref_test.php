<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function byrefVariadic(string $types, &...$vars) {
    if (isset($vars[0])) {
        $vars[0] = 'MUTATED';
    }
    return $vars;
}

echo "== spread of a variable array ==\n";
$params = ['alpha', 'beta'];
byrefVariadic('ss', ...$params);
var_export($params);
echo "\n";

echo "== spread of array_merge (temporary) ==\n";
$bindValues = ['ss', ['gamma']];
byrefVariadic($bindValues[0], ...array_merge($bindValues[1], ['delta']));
echo "no warning above means ok\n";
