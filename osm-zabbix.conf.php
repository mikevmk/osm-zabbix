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

// Zabbix location and credentials
$zbx_url = 'http://example.com/zabbix';
//$zbx_url = 'http://zabbix.example.com';
$zbx_api_user = 'apiusername';
$zbx_api_pass = 'apiuserpass';

// Problems display minimum severity (2 - Warning, 3 - Average, 4 - High...)
$zbx_min_severity = 3;

// Icons. URL path could be relative or absolute
$icon_ok = 'images/ok.png';
$icon_problem = 'images/error2.png';

// Center of the map by default
$center_lat = '-17.77639';
$center_lon = '31.000901';

// Zoom level by default
$zoom_level = 7;

// Set all cache_* vars to enable caching
$cache_dir = '/tmp';

// in seconds
$cache_ttl = 10;

?>
