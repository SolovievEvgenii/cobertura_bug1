<?php

use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Util\Filesystem;

if (!function_exists('isAjaxRequest')) {
    function isAjaxRequest(): bool
    {
        return !empty($_REQUEST['ajax_load']) || 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    }
}

if (!function_exists('zntSnakeToCamel')) {
    // qwe
    function zntSnakeToCamel(string $input, string $separator = '_')
    {
        return lcfirst(str_replace($separator, '', ucwords(strtolower($input), $separator)));
    }
}

if (!function_exists('cobertura2')) {
    // how to run:
    // vendor/bin/phpunit --coverage-php 1.php
    // php -r 'include("vendor/autoload.php");include("src/Helpers.php"); cobertura2("1.php");'
    function cobertura2(string $phpFormatFilepath, string $target = 'cobertura.xml')
    {
        function listOfchilds(&$arr, $bp, $currNode)
        {
            foreach ($currNode->children() as $item) {
                if ($item instanceof File) {
                    $path = str_replace($bp, '', $item->pathAsString());
                    $arr[$path] = $item;
                } elseif ($item instanceof Directory) {
                    listOfchilds($arr, $bp, $item);
                }
            }
        }
        $coverage = require($phpFormatFilepath);
        $time = (string) time();

        $report = $coverage->getReport();

        $implementation = new DOMImplementation;

        $documentType = $implementation->createDocumentType(
            'coverage',
            '',
            'http://cobertura.sourceforge.net/xml/coverage-04.dtd'
        );

        $document               = $implementation->createDocument('', '', $documentType);
        $document->xmlVersion   = '1.0';
        $document->encoding     = 'UTF-8';
        $document->formatOutput = true;

        $coverageElement = $document->createElement('coverage');

        $linesValid   = $report->numberOfExecutableLines();
        $linesCovered = $report->numberOfExecutedLines();
        $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);
        $coverageElement->setAttribute('line-rate', (string) $lineRate);

        $branchesValid   = $report->numberOfExecutableBranches();
        $branchesCovered = $report->numberOfExecutedBranches();
        $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);
        $coverageElement->setAttribute('branch-rate', (string) $branchRate);

        $coverageElement->setAttribute('lines-covered', (string) $report->numberOfExecutedLines());
        $coverageElement->setAttribute('lines-valid', (string) $report->numberOfExecutableLines());
        $coverageElement->setAttribute('branches-covered', (string) $report->numberOfExecutedBranches());
        $coverageElement->setAttribute('branches-valid', (string) $report->numberOfExecutableBranches());
        $coverageElement->setAttribute('complexity', '');
        $coverageElement->setAttribute('version', '0.4.535');
        $coverageElement->setAttribute('timestamp', $time);

        $document->appendChild($coverageElement);

        $sourcesElement = $document->createElement('sources');
        $coverageElement->appendChild($sourcesElement);

        $sourceElement = $document->createElement('source', $report->pathAsString());
        $sourcesElement->appendChild($sourceElement);

        $packagesElement = $document->createElement('packages');
        $coverageElement->appendChild($packagesElement);

        $complexity = 0;
        $childs = [];
        listOfchilds($childs, $report->pathAsString() . DIRECTORY_SEPARATOR, $report);
        echo 'compiled '.count($childs).PHP_EOL;
        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            $packageElement    = $document->createElement('package');
            $packageComplexity = 0;

            $relPath = str_replace($report->pathAsString() . DIRECTORY_SEPARATOR, '', $item->pathAsString());
            $packageElement->setAttribute('name', $relPath);

            $linesValid   = $item->numberOfExecutableLines();
            $linesCovered = $item->numberOfExecutedLines();
            $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);

            $packageElement->setAttribute('line-rate', (string) $lineRate);

            $branchesValid   = $item->numberOfExecutableBranches();
            $branchesCovered = $item->numberOfExecutedBranches();
            $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);

            $packageElement->setAttribute('branch-rate', (string) $branchRate);

            $packageElement->setAttribute('complexity', '');
            $packagesElement->appendChild($packageElement);

            $classesElement = $document->createElement('classes');

            $packageElement->appendChild($classesElement);

            $classes      = $item->classesAndTraits();
            $coverageData = $item->lineCoverageData();

            foreach ($classes as $className => $class) {
                $complexity += $class['ccn'];
                $packageComplexity += $class['ccn'];

                if (!empty($class['package']['namespace'])) {
                    $className = $class['package']['namespace'] . '\\' . $className;
                }

                $linesValid   = $class['executableLines'];
                $linesCovered = $class['executedLines'];
                $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);

                $branchesValid   = $class['executableBranches'];
                $branchesCovered = $class['executedBranches'];
                $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);

                $classElement = $document->createElement('class');

                $classElement->setAttribute('name', $className);
                $classElement->setAttribute('filename', $relPath);
                $classElement->setAttribute('line-rate', (string) $lineRate);
                $classElement->setAttribute('branch-rate', (string) $branchRate);
                $classElement->setAttribute('complexity', (string) $class['ccn']);

                $classesElement->appendChild($classElement);

                $methodsElement = $document->createElement('methods');

                $classElement->appendChild($methodsElement);

                $classLinesElement = $document->createElement('lines');

                $classElement->appendChild($classLinesElement);

                foreach ($class['methods'] as $methodName => $method) {
                    if ($method['executableLines'] === 0) {
                        continue;
                    }

                    preg_match("/\((.*?)\)/", $method['signature'], $signature);

                    $linesValid   = $method['executableLines'];
                    $linesCovered = $method['executedLines'];
                    $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);

                    $branchesValid   = $method['executableBranches'];
                    $branchesCovered = $method['executedBranches'];
                    $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);

                    $methodElement = $document->createElement('method');

                    $methodElement->setAttribute('name', $methodName);
                    $methodElement->setAttribute('signature', $signature[1]);
                    $methodElement->setAttribute('line-rate', (string) $lineRate);
                    $methodElement->setAttribute('branch-rate', (string) $branchRate);
                    $methodElement->setAttribute('complexity', (string) $method['ccn']);

                    $methodLinesElement = $document->createElement('lines');

                    $methodElement->appendChild($methodLinesElement);

                    foreach (range($method['startLine'], $method['endLine']) as $line) {
                        if (!isset($coverageData[$line]) || $coverageData[$line] === null) {
                            continue;
                        }
                        $methodLineElement = $document->createElement('line');

                        $methodLineElement->setAttribute('number', (string) $line);
                        $methodLineElement->setAttribute('hits', (string) count($coverageData[$line]));

                        $methodLinesElement->appendChild($methodLineElement);

                        $classLineElement = $methodLineElement->cloneNode();

                        $classLinesElement->appendChild($classLineElement);
                    }

                    $methodsElement->appendChild($methodElement);
                }
            }
            
            
            if ($report->numberOfFunctions() === 0) {
                $packageElement->setAttribute('complexity', (string) $packageComplexity);

                continue;
            } else {
                $functions = $report->functions();
                if ($child = ($childs[$relPath] ?? null) ){
                    $functions = $child->functions();
                    if (count($functions)==0) {
                        continue;
                    }
                }
            }

            $functionsComplexity      = 0;
            $functionsLinesValid      = 0;
            $functionsLinesCovered    = 0;
            $functionsBranchesValid   = 0;
            $functionsBranchesCovered = 0;

            $classElement = $document->createElement('class');
            $classElement->setAttribute('name', basename($item->pathAsString()));
            $classElement->setAttribute('filename', $relPath);

            $methodsElement = $document->createElement('methods');

            $classElement->appendChild($methodsElement);

            $classLinesElement = $document->createElement('lines');

            $classElement->appendChild($classLinesElement);

            foreach ($functions as $functionName => $function) {
                if ($function['executableLines'] === 0) {
                    continue;
                }

                $complexity += $function['ccn'];
                $packageComplexity += $function['ccn'];
                $functionsComplexity += $function['ccn'];

                $linesValid   = $function['executableLines'];
                $linesCovered = $function['executedLines'];
                $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);

                $functionsLinesValid += $linesValid;
                $functionsLinesCovered += $linesCovered;

                $branchesValid   = $function['executableBranches'];
                $branchesCovered = $function['executedBranches'];
                $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);

                $functionsBranchesValid += $branchesValid;
                $functionsBranchesCovered += $branchesValid;

                $methodElement = $document->createElement('method');

                $methodElement->setAttribute('name', $functionName);
                $methodElement->setAttribute('signature', $function['signature']);
                $methodElement->setAttribute('line-rate', (string) $lineRate);
                $methodElement->setAttribute('branch-rate', (string) $branchRate);
                $methodElement->setAttribute('complexity', (string) $function['ccn']);

                $methodLinesElement = $document->createElement('lines');

                $methodElement->appendChild($methodLinesElement);

                foreach (range($function['startLine'], $function['endLine']) as $line) {
                    if (!isset($coverageData[$line]) || $coverageData[$line] === null) {
                        continue;
                    }
                    $methodLineElement = $document->createElement('line');

                    $methodLineElement->setAttribute('number', (string) $line);
                    $methodLineElement->setAttribute('hits', (string) count($coverageData[$line]));

                    $methodLinesElement->appendChild($methodLineElement);

                    $classLineElement = $methodLineElement->cloneNode();

                    $classLinesElement->appendChild($classLineElement);
                }

                $methodsElement->appendChild($methodElement);
            }

            $packageElement->setAttribute('complexity', (string) $packageComplexity);

            if ($functionsLinesValid === 0) {
                continue;
            }

            $lineRate   = $functionsLinesCovered / $functionsLinesValid;
            $branchRate = $functionsBranchesValid === 0 ? 0 : ($functionsBranchesCovered / $functionsBranchesValid);

            $classElement->setAttribute('line-rate', (string) $lineRate);
            $classElement->setAttribute('branch-rate', (string) $branchRate);
            $classElement->setAttribute('complexity', (string) $functionsComplexity);

            $classesElement->appendChild($classElement);
        }

        $coverageElement->setAttribute('complexity', (string) $complexity);

        $buffer = $document->saveXML();

        if ($target !== null) {
            if (!strpos($target, '://') !== false) {
                Filesystem::createDirectory(dirname($target));
            }

            if (@file_put_contents($target, $buffer) === false) {
                throw new RuntimeException($target);
            }
        }

        return true;
    }
}