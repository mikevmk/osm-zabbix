<?php

/*
OpenStreetMap/OpenLayers support for Zabbix
Copyright (C) 2013 Mike Kuznetsov mike4gg@gmail.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require 'zabbixapi/ZabbixApiAbstract.class.php';
require 'zabbixapi/ZabbixApi.class.php';
require 'osm-zabbix.conf.php';

$groupid = $_GET["groupid"];
$type = $_GET["type"];

function connect_to_api($zbx_url,$zbx_api_user,$zbx_api_pass) {
    try {
        $api = new ZabbixApi($zbx_url . '/api_jsonrpc.php', $zbx_api_user, $zbx_api_pass);
        $api->setDefaultParams(array('output' => 'extend'));
        return $api;
    } catch(Exception $e) {
        die($e->getMessage()."\n");
    }
}

function get_groupids($api) {

    $hosts = $api->hostGet(array(
        'withInventory' => 'true',
        'selectInventory' => '["location_lat","location_lon"]'
    ));

    $hostids = array();
    foreach($hosts as $host) {
        if(is_numeric($host->inventory->location_lat) and is_numeric($host->inventory->location_lon)) {
            array_push($hostids,$host->hostid);
        }
    }

    $groups = $api->hostgroupGet(array(
        'hostids' => $hostids
    ));

    $groupids = array();
    foreach($groups as $group) {
        array_push($groupids,$group->groupid);
    }

    return $groupids;
}

function get_group_name($api,$groupid) {

    $groups = $api->hostgroupGet(array(
        'groupids' => $groupid
    ));

    return $groups[0]->name;
}

function is_cache_fresh($cache_file,$cache_ttl) {
    if(file_exists($cache_file)) {
        if((time()-filemtime($cache_file))<$cache_ttl) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function write_cache($cache_dir,$cache_file,$layer) {
    if(is_writable($cache_dir)) {
        file_put_contents($cache_file, $layer, LOCK_EX);
    }
}

if(isset($groupid) and intval($groupid)>0 and ($type == 'ok' or $type == 'problems')) {

    if(isset($cache_dir) and isset($cache_ttl)) {

        $cache_file = $cache_dir . '/layer-' . $groupid . '-' . $type . '.cache';

        if(is_cache_fresh($cache_file,$cache_ttl)) {
            print(file_get_contents($cache_file));
            exit;
        }
    }

    $api = connect_to_api($zbx_url,$zbx_api_user,$zbx_api_pass);

    $groupname = get_group_name($api,$groupid);

    $hosts = $api->hostGet(array(
        'withInventory' => 'true',
        'selectInventory' => '["location_lat","location_lon"]',
        'groupids' => $groupid
    ));

    $triggers = $api->triggerGet(array(
        'min_severity' => $zbx_min_severity,
        'only_true' => 'true',
        'expandData' => 'true'
    ));

    $hostids = array();
    $problems = array();
    $icons = array();
    $points = array();
    $hostnames = array();

    foreach($hosts as $host) {
        if(is_numeric($host->inventory->location_lat) and is_numeric($host->inventory->location_lon)) {
            $hostid = $host->hostid;
            array_push($hostids, $hostid);
            $problem = '';
            foreach($triggers as $trigger) {
                if($trigger->hosts[0]->hostid == $hostid) {
                    $trigger_url = "<br/><a href=\"" . $zbx_url . "/events.php?triggerid=" . $trigger->triggerid . "&hostid=" . $hostid . "&request=events.php%3Ftriggerid%3D" . $trigger->triggerid . "%26hostid%3D" . $hostid . "\">" . $trigger->description . "</a> (since " . date("D M j G:i:s", $trigger->lastchange) . ")";
                    $problem = $problem . $trigger_url;
                }
            }
            if(empty($problem)) {
                $icons[$hostid] = $icon_ok;
                $problems[$hostid] = 'OK';
            } else {
                $icons[$hostid] = $icon_problem;
                $problems[$hostid] = $problem;
            }
            $points[$hostid] = $host->inventory->location_lat . "," . $host->inventory->location_lon;
            $hostnames[$hostid] = $host->name;
        }
    }

    $overview_url = "/overview.php?type=0&groupid=" . $groupid . "&request=overview.php%3Ftype%3D0%26groupid%3D" . $groupid;
    $overview_url = "<br/><br/><a href=\"" . $zbx_url . $overview_url . "\">Group overview</a>";
    $layer = "point\ttitle\tdescription\ticon\n";

    foreach($hostids as $hostid) {
        if(($type == 'problems' and $problems[$hostid] != 'OK') or ($type == 'ok' and $problems[$hostid] == 'OK')) {
            $layer = $layer . $points[$hostid] . "\t" . $groupname . ": " . $hostnames[$hostid] . "\t" . "Trigger(s): " . $problems[$hostid] . $overview_url . "\t" . $icons[$hostid] . "\n";
        }
    }

    if(isset($cache_file)) {
        if(is_writable($cache_dir)) {
            file_put_contents($cache_file, $layer, LOCK_EX);
        }
    }

    print($layer);

}

?>
