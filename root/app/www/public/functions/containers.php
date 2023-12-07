<?php

/*
----------------------------------
 ------  Created: 112923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function renderContainerRow($hash)
{
    global $pullsFile, $settingsFile, $processList, $dockerStats;

    $container = findContainerFromHash($hash);

    if (!$pullsFile) {
        $pullsFile = getServerFile('pull');
        if ($pullsFile['code'] != 200) {
            $apiError = $pullsFile['file'];
        }
        $pullsFile = $pullsFile['file'];
    }

    if (!$settingsFile) {
        $settingsFile = getServerFile('settings');
        if ($settingsFile['code'] != 200) {
            $apiError = $settingsFile['file'];
        }
        $settingsFile = $settingsFile['file'];
    }

    if (!$processList) {
        $processList = apiRequest('dockerProcessList', ['useCache' => false, 'format' => true]);
        logger(UI_LOG, 'dockerProcessList:' . json_encode($processList));
        $processList = json_decode($processList['response']['docker'], true);
    }

    if (!$dockerStats) {
        $dockerStats = apiRequest('dockerStats', ['useCache' => false]);
        logger(UI_LOG, 'dockerStats:' . json_encode($dockerStats));
        $dockerStats = json_decode($dockerStats['response']['docker'], true);
    }

    $containerProcess = $containerStats = [];

    if (is_array($container)) {
        foreach ($processList as $process) {
            if (!is_array($process)) {
                continue;
            }

            if ($process['Names'] == $container['Names']) {
                $containerProcess = $process;
                break;
            }
        }

        foreach ($dockerStats as $dockerStat) {
            if ($dockerStat['Name'] == $container['Names']) {
                $containerStats = $dockerStat;
                break;
            }
        }
    }

    $control = $containerProcess['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $hash . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $hash . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $hash . '\', \'start\')">Start</button>';

    $pullData = $pullsFile[$hash];
    $updateStatus = '<span class="text-danger">Unknown</span>';
    if ($pullData) {
        $updateStatus = ($pullData['image'] == $pullData['container']) ? '<span class="text-success">Updated</span>' : '<span class="text-warning">Outdated</span>';
    }

    $cpuUsage = floatval(str_replace('%', '', $containerStats['CPUPerc']));
    if (intval($settingsFile['global']['cpuAmount']) > 0) {
        $cpuUsage = number_format(($cpuUsage / intval($settingsFile['global']['cpuAmount'])), 2) . '%';
    }

    $health = 'Unknown';
    if (strpos($containerProcess['Status'], 'healthy') !== false) {
        $health = 'Healthy';
    }
    if (strpos($containerProcess['Status'], 'unhealthy') !== false) {
        $health = 'Unhealthy';
    }

    $return     = [
                    'control'   => $control,
                    'update'    => $updateStatus,
                    'state'     => $containerProcess['State'],
                    'running'   => $containerProcess['RunningFor'],
                    'status'    => $containerProcess['Status'],
                    'cpu'       => $cpuUsage,
                    'cpuTitle'  => $containerStats['CPUPerc'],
                    'mem'       => $containerStats['MemPerc'],
                    'health'    => $health
                ];

    return $return;
}