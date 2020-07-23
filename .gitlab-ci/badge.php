<?php
/* --------------------------------------------------------------
   badge.php 2020-07-22
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   --------------------------------------------------------------
*/

/**
 * This script depends on the following files to be present before it can be started:
 * It needs a coverage report under badges/coverage/index.xml
 * It needs a violations report under badges/violations.xml
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PUGX\Poser\Render\SvgRender;
use PUGX\Poser\Poser;

// Make the folder for the badges if it does not exist yet
if (!@mkdir("badges") && !is_dir("badges")) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', "badges"));
}

/**
 * COVERAGE SECTION
 */
$coveragePercentile = getCoveragePercentile();

$coverageBadge = null;

if ($coveragePercentile) {
    
    if ($coveragePercentile < 40) {
        $coverageBadge = generateDangerBadge('coverage', $coveragePercentile . '%');
    } elseif ($coveragePercentile < 80) {
        $coverageBadge = generateWarningBadge('coverage', $coveragePercentile . '%');
    } else {
        $coverageBadge = generatePositiveBadge('coverage', $coveragePercentile . '%');
    }
} else {
    $coverageBadge = generateDangerBadge('coverage', 'ERROR');
}

file_put_contents(__DIR__ . '/../badges/coverage.svg', $coverageBadge);

function getCoveragePercentile()
{
    
    $coverageReportPath = __DIR__ . '/../badges/coverage/index.xml';
    
    if (file_exists($coverageReportPath)) {
        $parser = xml_parser_create('UTF-8');
        
        $coverageReportContents = file_get_contents($coverageReportPath);
        
        xml_parse_into_struct($parser, $coverageReportContents, $coverageReport);
        
        $rootFound = false;
        foreach ($coverageReport as $coverageEntity) {
            if ($coverageEntity['tag'] === 'DIRECTORY' && isset($coverageEntity['attributes']['NAME'])
                && $coverageEntity['attributes']['NAME'] === '/') {
                $rootFound = true;
            }
            
            if ($rootFound && $coverageEntity['tag'] === 'LINES') {
                return $coverageEntity['attributes']['PERCENT'];
            }
        }
    }
    
    return null;
}

/**
 * UNIT TEST SECTION
 */

$testsPassing = areUnitTestsWorking();

$testsBadge = null;

if ($testsPassing !== null) {
    $testsBadge = $testsPassing ? generatePositiveBadge('tests', 'PASSING') : generateDangerBadge('tests', 'FAILING');
} else {
    $testsBadge = generateDangerBadge('tests', 'ERROR');
}

file_put_contents(__DIR__ . '/../badges/tests.svg', $testsBadge);

function areUnitTestsWorking()
{
    $coverageReportPath = __DIR__ . '/../badges/coverage/index.xml';
    
    if (file_exists($coverageReportPath)) {
        $parser = xml_parser_create('UTF-8');
        
        $coverageReportContents = file_get_contents($coverageReportPath);
        
        xml_parse_into_struct($parser, $coverageReportContents, $coverageReport);
        
        foreach ($coverageReport as $coverageEntity) {
            if ($coverageEntity['tag'] === 'TEST' && isset($coverageEntity['attributes']['STATUS'])
                && $coverageEntity['attributes']['STATUS'] !== 'PASSED') {
                return false;
            }
        }
        
        return true;
    }
    
    return null;
}

/**
 * METRICS SECTION
 */

$violationCount = getMaintainabilityPercentile();

$violationsBadge = null;

if ($violationCount) {
    
    if ($violationCount < 6) {
        $violationsBadge = generatePositiveBadge('violations', $violationCount);
    } elseif ($violationCount < 11 && $violationCount > 5) {
        $violationsBadge = generateWarningBadge('violations', $violationCount);
    } else {
        $violationsBadge = generateDangerBadge('violations', $violationCount);
    }
} else {
    $violationsBadge = generateDangerBadge('violations', 'ERROR');
}

file_put_contents(__DIR__ . '/../badges/violations.svg', $violationsBadge);

function getViolationCount()
{
    
    $violationReportPath = __DIR__ . '/../badges/violations.xml';
    
    if (file_exists($violationReportPath)) {
        $parser = xml_parser_create('UTF-8');
        
        $violationReportContents = file_get_contents($violationReportPath);
        
        xml_parse_into_struct($parser, $violationReportContents, $violationReport);
        
        $violationCounter = 0;
        foreach ($violationReport as $violationEntity) {
            if ($violationEntity['tag'] === 'VIOLATION') {
                $violationCounter++;
            }
        }
        
        return $violationCounter;
    }
    
    return null;
}

/**
 * BADGE GENERATION SECTION
 */

function generateDangerBadge($title, $value)
{
    $render = new SvgRender();
    $poser  = new Poser([$render]);
    
    return $poser->generate($title, $value, 'f22613', 'plastic');
}

function generateWarningBadge($title, $value)
{
    $render = new SvgRender();
    $poser  = new Poser([$render]);
    
    return $poser->generate($title, $value, 'f5ab35', 'plastic');
}

function generatePositiveBadge($title, $value)
{
    $render = new SvgRender();
    $poser  = new Poser([$render]);
    
    return $poser->generate($title, $value, '2ecc71', 'plastic');
}