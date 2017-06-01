<?php

chdir('../../');
include_once('./include/auth.php');
include_once('./include/config.php');
include_once($config['library_path'] . '/database.php');

// include the weathermap class so that we can get the version
include_once(dirname(__FILE__) . '/lib/Weathermap.class.php');
include_once(dirname(__FILE__) . '/lib/database.php');
include_once(dirname(__FILE__) . '/lib/WeathermapManager.class.php');

$weathermap_confdir = realpath(dirname(__FILE__) . '/configs');

$i_understand_file_permissions_and_how_to_fix_them = FALSE;
$my_name = 'weathermap-cacti10-plugin-mgmt.php';

$manager = new WeathermapManager(weathermap_get_pdo(), $weathermap_confdir);

set_default_action();

switch (get_request_var('action')) {
    case 'dump_maps':
       	header('Content-type: application/json'); 

		$data = array(
			'maps'=>$manager->getMaps(),
			'groups'=>$manager->getGroups()
		);

		print json_encode($data);

		break;
    case 'enable_poller_output':
        weathermap_setting_save(0, 'rrd_use_poller_output', 1);
        header('Location: weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=0&header=false');

        break;
    case 'group_update':
        $id = -1;
		if (isset_request_var('id')) {
			$id = get_filter_request_var('id');
		}
		$newname = get_nfilter_request_var('gname');

        if ($id >= 0 && $newname != '') weathermap_group_update($id, $newname);
        if ($id < 0 && $newname != '') weathermap_group_create($newname);

        header('Location: weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');

        break;
    case 'groupadmin_delete':
        $id = -1;
		if (isset_request_var('id')) {
			$id = get_filter_request_var('id');
		}

        if ($id >= 1) {
            weathermap_group_delete($id);
        }

        header('Location: weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');

        break;
    case 'group_form':
        $id = -1;
		if (isset_request_var('id')) {
			$id = get_filter_request_var('id');
		}

		top_header();

        if ($id >= 0) {
            weathermap_group_form($id);
        }

        weathermap_footer_links();
		bottom_footer();

        break;
    case 'groupadmin':
		top_header();
        weathermap_group_editor();
        weathermap_footer_links();
		bottom_footer();

        break;
    case 'chgroup_update':
        $mapid = -1;
        $groupid = -1;

		if (isset_request_var('map_id')) {
            $mapid = get_filter_request_var('map_id');
        }

		if (isset_request_var('new_group')) {
            $groupid = get_filter_request_var('new_group');
        }

        if (($groupid > 0) && ($mapid >= 0)) {
            weathermap_set_group($mapid, $groupid);
        }

        header('Location: ' . $my_name . (strpos($my_name, '?') === false ? '?':'&') . 'header=false');

        break;
    case 'chgroup':
		if (isset_request_var('id')) {
			top_header();
            weathermap_chgroup(get_filter_request_var('id'));
			bottom_footer();
        } else {
            print __('Something got lost back there.');
        }

        break;
    case 'map_settings_delete':
        $mapid = NULL;
        $settingid = NULL;

		if (isset_request_var('map_id')) {
            $mapid = get_filter_request_var('map_id');
        }

		if (isset_request_var('id')) {
            $settingid = get_filter_request_var('id');
        }

        if (!is_null($mapid) && !is_null($settingid)) {
            // create setting
            weathermap_setting_delete($mapid, $settingid);
        }

        header('Location: weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=' . $mapid . '&header=false');

        break;
    case 'save':
		// this is the save option from the map_settings_form
        $mapid     = NULL;
        $settingid = NULL;
        $name      = '';
        $value     = '';

		if (isset_request_var('map_id')) {
            $mapid = get_filter_request_var('map_id');
        }

		if (isset_request_var('id')) {
            $settingid = get_filter_request_var('id');
        }

		if (isset_request_var('name')) {
            $name = get_nfilter_request_var('name');
        }

		if (isset_request_var('value')) {
            $value = get_nfilter_request_var('value');
        }

        if (!is_null($mapid) && $settingid == 0) {
            // create setting
            weathermap_setting_save($mapid, $name, $value);
        } elseif (!is_null($mapid) && !is_null($settingid)) {
            // update setting
            weathermap_setting_update($mapid, $settingid, $name, $value);
        }

        header('Location: weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=' . $mapid . '&header=false');

        break;
    case 'map_settings_form':
        if (isset_request_var('mapid')) {
			top_header();

			if (isset_request_var('id')) {
                weathermap_map_settings_form(get_filter_request_var('mapid'), get_filter_request_var('id'));
            } else {
                weathermap_map_settings_form(get_filter_request_var('mapid'));
            }

            weathermap_footer_links();

			bottom_footer();
        }

        break;
    case 'map_settings':
        if (isset_request_var('id')) {
			top_header();
            weathermap_map_settings(get_request_var('id'));
            weathermap_footer_links();
			bottom_footer();
        }

        break;
    case 'perms_add_user':
        if (isset_request_var('mapid') && isset_request_var('userid')) {
			$mapid  = get_filter_request_var('mapid');
			$userid = get_filter_request_var('userid');

            perms_add_user($mapid, $userid);
            header('Location: weathermap-cacti10-plugin-mgmt.php?action=perms_edit&id=' . $mapid . '&header=false');
        }

        break;
    case 'perms_delete_user':
        if (isset_request_var('mapid') && isset_request_var('userid')) {
			$mapid  = get_filter_request_var('mapid');
			$userid = get_filter_request_var('userid');

            perms_delete_user($mapid, $userid);
            header('Location: weathermap-cacti10-plugin-mgmt.php?action=perms_edit&id=' . $mapid . '&header=fasle');
        }

        break;
    case 'perms_edit':
		if (isset_request_var('id')) {
			top_header();
            perms_list(get_filter_request_var('id'));
			bottom_footer();
        } else {
            print __('Something got lost back there.');
        }

        break;
    case 'delete_map':
		if (isset_request_var('id')) {
			map_delete(get_filter_request_var('id'));
		}

        header('Location: weathermap-cacti10-plugin-mgmt.php?header=false');

        break;
    case 'deactivate_map':
		if (isset_request_var('id')) {
			map_deactivate(get_filter_request_var('id'));
		}

        header('Location: ' . $my_name . (strpos($my_name, '?') === false ? '?':'&') . 'header=false');

        break;
    case 'activate_map':
		if (isset_request_var('id')) {
			map_activate(get_filter_request_var('id'));
		}

        header('Location: ' . $my_name . (strpos($my_name, '?') === false ? '?':'&') . 'header=false');

        break;
    case 'move_map_up':
		if (isset_request_var('id')) {
            map_move(get_filter_request_var('id'), null, -1);
		}

        header('Location: ' . $my_name . (strpos($my_name, '?') === false ? '?':'&') . 'header=false');

        break;
    case 'move_map_down':
		if (isset_request_var('id')) {
            map_move(get_filter_request_var('id'), null, +1);
		}

        header('Location: ' . $my_name . (strpos($my_name, '?') === false ? '?':'&') . 'header=false');

        break;
    case 'move_group_up':
		if (isset_request_var('id')) {
			weathermap_group_move(get_filter_request_var('id'), -1);
		}

        header('Location: weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');

        break;
    case 'move_group_down':
		if (isset_request_var('id')) {
            weathermap_group_move(get_filter_request_var('id'), 1);
		}

        header('Location: weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');

        break;
    case 'viewconfig':
		top_header();

		if (isset_request_var('file')) {
            preview_config(get_nfilter_request_var('file'));
        } else {
            print __('No such file.');
        }

		bottom_footer();
        break;

    case 'addmap_picker':
		top_header();

        if (isset_request_var('show') && get_nfilter_request_var('show') == 'all') {
            addmap_picker(true);
        } else {
            addmap_picker(false);
        }

		bottom_footer();

        break;
    case 'addmap':
        if (isset_request_var('file')) {
            add_config(get_nfilter_request_var('file'));
            header('Location: ' . $my_name . (strpos($my_name, '?') === false ? '?':'&') . 'header=false');
        } else {
            print __('No such file.');
        }

        break;
    case 'rebuildnow':
		top_header();

        print '<h3>' . __('REALLY Rebuild all maps?') . '</h3><b>' . __('NOTE: Because your Cacti poller process probably doesn\'t run as the same user as your webserver, it\'s possible this will fail with file permission problems even though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if your mod_php/ISAPI module uses a different php.ini to your command-line PHP.') . '</b><hr>';

        print '<p>' . __('It is recommended that you don\'t use this feature, unless you understand and accept the problems it may cause.') . '</p>';
        print '<h4><a href="?action=rebuildnow2">' . __('Yes') . '</a></h4>';
        print '<h1><a href="weathermap-cacti10-plugin-mgmt.php">' . __('No'). '</a></h1>';

		bottom_footer();

        break;
    case 'rebuildnow2':
        include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Weathermap.class.php';
        include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'poller-common.php';

		top_header();

        print '<h3>' . __('Rebuilding all maps') . '</h3><b>' . __('NOTE: Because your Cacti poller process probably doesn\'t run as the same user as your webserver, it\'s possible this will fail with file permission problems even though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if your mod_php/ISAPI module uses a different php.ini to your command-line PHP.') . '</b><hr><pre>';

        weathermap_run_maps(dirname(__FILE__));

        print '</pre>';
        print '<hr /><h3>' . __('Done.') . '</h3>';

		bottom_footer();

        break;
    default:
    	// by default, just list the map setup
		top_header();

        maplist();
        weathermap_footer_links();

		bottom_footer();
        break;
}

///////////////////////////////////////////////////////////////////////////

function weathermap_footer_links() {
    global $WEATHERMAP_VERSION;
    print '<br />';
    html_start_box('<center><a target="_blank" class="linkOverDark" href="docs/">' . __('Local Documentation') . '</a> -- <a target="_blank" class="linkOverDark" href="http://www.network-weathermap.com/">' . __('Weathermap Website') . '</a> -- <a target="_target" class="linkOverDark" href="weathermap-cacti10-plugin-editor.php">' . __('Weathermap Editor') . '</a>' . __('-- This is version %s', $WEATHERMAP_VERSION) . '</center>', '100%', '', '2', 'center', '');
    html_end_box();
}


function maplist_warnings() {
    global $manager;

    if (!wm_module_checks()) {
        print '<div align="center" class="wm_warning"><p>';

        print '<b>' . __('Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.') . '</b><p>';
        print __('If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.') . '<p>';
        print '<p>' . __('You should also run <a href=\'check.php\'>check.php</a> to help make sure that there are no problems.') . '</p><hr/>';


        print '</p></div>';
        exit();
    }

    $tables = weathermap_get_table_list(weathermap_get_pdo());
    if (!in_array('weathermap_maps', $tables)) {
        print '<div align="center" class="wm_warning"><p>';
        print __('The weathermap_maps table is missing completely from the database. Something went wrong with the installation process.');
        print '</p></div>';
    }

    $boost_enabled = $manager->getAppSetting('boost_rrd_update_enable', 'off');

    if ($boost_enabled == 'on') {

        $has_global_poller_output = $manager->getMapSettingByName(0, 'rrd_use_poller_output', false);

        if (!$has_global_poller_output) {
            print '<div align="center" class="wm_warning"><p>';
            print __('You are using the Boost plugin to update RRD files. Because this delays data being written to the files, it causes issues with Weathermap updates. You can resolve this by using Weathermap\'s \'poller_output\' support, which grabs data directly from the poller. <a href="weathermap-cacti10-plugin-mgmt.php?action=enable_poller_output">You can enable that globally by clicking here.') . '</a>';
            print '</p></div>';
        }
    }

    $last_started     = $manager->getAppSetting('weathermap_last_started_file', true);
    $last_finished    = $manager->getAppSetting('weathermap_last_finished_file', true);
    $last_start_time  = intval($manager->getAppSetting('weathermap_last_start_time', true));
    $last_finish_time = intval($manager->getAppSetting('weathermap_last_finish_time', true));
    $poller_interval  = intval($manager->getAppSetting('poller_interval'));

    maplist_warnings();

    if (($last_finish_time - $last_start_time) > $poller_interval) {
        if (($last_started != $last_finished) && ($last_started != '')) {
            print '<div align="center" class="wm_warning"><p>';
            print __('Last time it ran, Weathermap did NOT complete it\'s run. It failed during processing for \'%s\'', $last_started);
            print __('This <b>may</b> have affected other plugins that run during the poller process.') . '</p><p>';
            print __('You should either disable this map, or fault-find. Possible causes include memory_limit issues. The log may have more information.');
            print '</p></div>';
        }
    }

}


function maplist()
{
	global $i_understand_file_permissions_and_how_to_fix_them;
	global $manager;
	global $config;

	html_start_box(__('Weathermaps'), '100%', '', '3', 'center', 'weathermap-cacti10-plugin-mgmt.php?action=addmap_picker');

	$display_array = array(
		__('Config File'), 
		__('Title'), 
		__('Group'), 
		__('Last Run'),
		__('Active'),
		__('Settings'), 
		__('Sort Order'), 
		__('Accessible By')
	);

	html_header($display_array, 2);

	$userlist = $manager->getUserList();
	$users[0] = __('Anyone');

	foreach ($userlist as $user) {
		$users[$user->id] = $user->username;
	}

	$i = 0;

	$maps = $manager->getMapsWithGroups();

	$previous_id = -2;
	$had_warnings = 0;
	if (is_array($maps)) {
		form_alternate_row();

		print '<td>' . __('ALL MAPS') . '</td><td>' . __('(special settings for all maps)') . '</td><td></td><td></td>';
        print '<td></td>';

		print '<td><a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=0">';
		$setting_count = $manager->getMapSettingCount(0, 0);
		if ($setting_count > 0) {
			print sprintf(__n('%d special', '%d specials', $setting_count), $setting_count);
		} else {
			print __('standard');
		}
		print '</a>';

		print '</td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';

		foreach ($maps as $map) {
			form_alternate_row();

			print '<td><a title="'.  __('Click to start editor with this file') . '" href="weathermap-cacti10-plugin-editor.php?action=nothing&mapname=' . htmlspecialchars($map->configfile) . '">' . htmlspecialchars($map->configfile) . '</a>';
			print '</td>';

			print '<td>' . htmlspecialchars($map->titlecache) . '</td>';
			print '<td><a class="hyperLink" title="' . __('Click to change group') . '" class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=chgroup&id=' . $map->id . '">' . htmlspecialchars($map->groupname) . '</a></td>';


            print "<td>";
            print sprintf("%.2gs", $map->runtime);
            if ($map->warncount>0) {
                $had_warnings++;
                print '<br><a href="../../utilities.php?tail_lines=500&message_type=2&action=view_logfile&filter='.urlencode($map->configfile).'" title="Check cacti.log for this map"><img border=0 src="cacti-resources/img/exclamation.png" title="'.$map->warncount.' warnings last time this map was run. Check your logs.">'.$map->warncount."</a>";
            }
            print "</td>";

			if ($map->active == 'on') {
				print '<td class="wm_enabled"><a title="' . __('Click to Deactivate') . '" class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=deactivate_map&id=' . $map->id . '"><font color="green">' . __('Yes') . '</font></a>';
			} else {
				print '<td class="wm_disabled"><a title="' . __('Click to Activate') . '" class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=activate_map&id=' . $map->id . '"><font color="red">' . __('No') . '</font></a>';
			}
			print '<td>';

			print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=' . $map->id . '">';
			$setting_count = $manager->getMapSettingCount($map->id);
			if ($setting_count > 0) {
                print sprintf(__n('%d special', '%d specials', $setting_count), $setting_count);
			} else {
				print __('standard');
			}
			print '</a>';
			print '</td>';

			print '<td>';
			print '<span class="remover fa fa-caret-up moveArrow" href="weathermap-cacti10-plugin-mgmt.php?action=move_map_up&order='   . $map->sortorder . '&id=' . $map->id . '" title="' . __('Move Map Up') . '"></span>';
			print '<span class="remover fa fa-caret-down moveArrow" href="weathermap-cacti10-plugin-mgmt.php?action=move_map_down&order=' . $map->sortorder . '&id=' . $map->id . '" title="' . __('Move Map Down') . '"></span>';
			print '</td>';

			print '<td>';

			$userlist = $manager->
			getMapAuthUsers($map->id);
			$mapusers = array();
			foreach ($userlist as $user) {
				if (array_key_exists($user->userid, $users)) {
					$mapusers[] = $users[$user->userid];
				}
			}

			print '<a title="' . __('Click to edit permissions') . '" href="weathermap-cacti10-plugin-mgmt.php?action=perms_edit&id=' . $map->id . '">';
			if (count($mapusers) == 0) {
				print __('(no users)');
			} else {
				print join(', ', $mapusers);
			}
			print '</a>';

            print '</td>';
            print '<td class="right">';
            print '<span class="remover fa fa-remove deleteMarker" href="weathermap-cacti10-plugin-mgmt.php?action=delete_map&id=' . $map->id . '" title="' . __('Delete Map') . '"></span>';
            print '</td>';

            print '</tr>';
            $i++;
        }
    }

	if ($i == 0) {
		print '<tr><td colspan="4"><em>' . __('No Weathermaps Configured') . '</em></td></tr>';
	}

	html_end_box();

	$last_stats = $manager->getAppSetting('weathermap_last_stats', '');

	if ($last_stats != '') {
		print '<div align="center">' . __('Last Completed Run: %s', $last_stats) . '</div>';
	} else {

	}

	if ($had_warnings > 0) {
		print '<div align="center" class="wm_warning">';
		print sprintf(__n('%s of your maps had warnings last time it ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count)', '%s of your maps had warnings last time it ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count)', $had_warnings), $had_warnings);
		print '</div>';
	}

	print '<div class="break"></div>';
	print '<div align="center">';
	print '<input type="button" id="edit" value="' . __('Edit Groups')  . '">';
	print '<input type="button" id="settings" value="' . __('Settings') . '">';

	if ($i > 0 && $i_understand_file_permissions_and_how_to_fix_them) {
		print '<input type="button" id="recalc" value="' . __('Rebuild All') . '" title="' . __('Note: Experimental - You should NOT need to use this normally') . '">';
	}

	print '</div>';

	?>
	<script type='text/javascript'>
	$(function() {
		$('#settings').click(function(event) {
			document.location = urlPath + 'settings.php?tab=maps';
		});

		$('#edit').click(function(event) {
			event.preventDefault();
			loadPageNoHeader('weathermap-cacti10-plugin-mgmt.php?action=groupadmin&header=false');
		});

		$('#recalc').click(function(event) {
			event.preventDefault();
			loadPageNoHeader('weathermap-cacti10-plugin-mgmt.php?action=recalc&header=false');
		});

		$('.remover').click(function() {
			href=$(this).attr('href');
			loadPageNoHeader(href);
		});
	});
	</script>
	<?php
}

function addmap_picker($show_all = false) {
	global $weathermap_confdir;
	global $manager;

	$loaded = array();
	$flags = array();

	// find out what maps are already in the database, so we can skip those
	$existing_maps = $manager->getMaps();
	if (is_array($existing_maps)) {
		foreach ($existing_maps as $map) {
			$loaded[] = $map->configfile;
		}
	}

	html_start_box(__('Available Weathermap Configuration Files'), '100%', '', '1', 'center', '');

	if (is_dir($weathermap_confdir)) {
		$n = 0;
		$dh = opendir($weathermap_confdir);
		if ($dh) {
			$i = 0;
			$skipped = 0;
			html_header(array('', '', __('Config File'), __('Title'), ''), 2);

			while ($file = readdir($dh)) {
				$realfile = $weathermap_confdir . '/' . $file;

				// skip .-prefixed files like .htaccess, since it seems
				// that otherwise people will add them as map config files.
				// and the index.php too - for the same reason
				if (substr($file, 0, 1) != '.' && $file != 'index.php') {
					$used = in_array($file, $loaded);
					$flags[$file] = '';
					if ($used) $flags[$file] = 'USED';

					if (is_file($realfile)) {
						if ($used && !$show_all) {
							$skipped++;
						} else {
							$title = $manager->extractMapTitle($realfile);
							$titles[$file] = $title;
							$i++;
						}
					}
				}
			}
			closedir($dh);

			if ($i > 0) {
				ksort($titles);

				foreach ($titles as $file => $title) {
					$title = $titles[$file];
					form_alternate_row();
					print '<td><a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=addmap&file=' . $file . '" title="' . __('Add the configuration file') . '">' . __('Add') . '</a></td>';
					print '<td><a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=viewconfig&file=' . $file . '" title="' . __('View the configuration file in a new window'). '" target="_blank">' . __('View') . '</a></td>';
					print '<td>' . htmlspecialchars($file);
					if ($flags[$file] == 'USED') print ' <b>' . __('(USED)') . '</b>';
					print '</td>';
					print '<td><em>' . htmlspecialchars($title) . '</em></td>';
					print '</tr>';
				}
			}

			if (($i + $skipped) == 0) {
				print '<tr><td>' . __('No files were found in the configs directory.') . '</td></tr>';
			}

			if (($i == 0) && $skipped > 0) {
				print '<tr><td>' . __('(%s files weren\'t shown because they are already in the database.', $skipped) . '</td></tr>';
			}
		} else {
			print '<tr><td>' . __('Can\'t open %s to read - you should set it to be readable by the webserver.', $weathermap_confdir) . '</td></tr>';
		}
	} else {
		print '<tr><td>' . __('There is no directory named %s - you will need to create it, and set it to be readable by the webserver. If you want to upload configuration files from inside Cacti, then it should be <i>writable</i> by the webserver too.', $weathermap_confdir) . '</td></tr>';
	}

	html_end_box();

	if ($skipped > 0) {
		print '<p align="center">' . __('Some files are not shown because they have already been added. You can <a href="weathermap-cacti10-plugin-mgmt.php?action=addmap_picker&show=all">show these files too</a>, if you need to.') . '</p>';
	}

	if ($show_all) {
		print '<p align="center">' . __('Some files are shown even though they have already been added. You can <a href="weathermap-cacti10-plugin-mgmt.php?action=addmap_picker">hide those files too</a>, if you need to.') . '</p>';
	}
}

function preview_config($file) {
	global $weathermap_confdir;

	chdir($weathermap_confdir);

	$path_parts = pathinfo($file);
	$file_dir = realpath($path_parts['dirname']);

	if ($file_dir != $weathermap_confdir) {
        // someone is trying to read arbitrary files?
        // print '$file_dir != $weathermap_confdir';
        print '<h3>' . __('Path mismatch') . '</h3>';
	} else {
		html_start_box(__('Preview of %s', $file), '100%', '', '3', 'center', '');

		print '<tr><td class="textArea">';
		print '<pre>';

		$realfile = $weathermap_confdir . '/' . $file;

		if (is_file($realfile)) {
			$fd = fopen($realfile, 'r');
			while (!feof($fd)) {
				$buffer = fgets($fd, 4096);
				print $buffer;
			}
			fclose($fd);
		}

		print '</pre>';
		print '</td></tr>';
		html_end_box();
	}
}

function perms_list($id) {
	global $manager;

	$map = $manager->getMap($id);
	$title = $map->titlecache;

	$users = $manager->getUserList(true);
	$auth = $manager->getMapAuth($id);

	$mapuserids = array();

	// build an array of userids that are allowed to see this map (and that actually exist)
	foreach ($auth as $user) {
		if (isset($users[$user->userid])) {
 			$mapuserids[] = $user->userid;
		}
	}

	// now build the list of users that exist but aren't currently allowed (for the picklist)
	$candidate_users = array();
	foreach ($users as $uid => $user) {
		if (!in_array($uid, $mapuserids)) {
			$candidate_users [] = $user;
		}
	}

	html_start_box(__('Edit permissions for Weathermap %s: %s', $id, $title), '100%', '', '2', 'center', '');

	html_header(array(__('Username'), ''));

	foreach ($mapuserids as $user) {
		form_alternate_row();
		print '<td>' . htmlspecialchars($users[$user]->username) . '</td>';
		print '<td><a href="weathermap-cacti10-plugin-mgmt.php?action=perms_delete_user&mapid=' . $id . '&userid=' . $user . '"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="' . __('Remove permissions for this user to see this map'). '"></a></td>';

		print '</tr>';
	}

	if (sizeof($mapuserids) == 0) {
		print '<tr><td><em>' . __('nobody can see this map') . '</em></td></tr>';
	}

	html_end_box();

	html_start_box('', '100%', '', '3', 'center', '');

	print '<tr>';

	if (sizeof($candidate_users) == 0) {
		print '<td><em>' . __('There aren\'t any users left to add!') . '</em></td></tr>';
	} else {
		print '<td><form action="">' . __('Allow') . ' <input type="hidden" name="action" value="perms_add_user"><input type="hidden" name="mapid" value="' . $id . '"><select name="userid">';
		foreach ($candidate_users as $user) {
			printf('<option value="%s">%s</option>', $user->id, $user->username);
		}

		print '</select> ' . __('to see this map') . ' <input type="submit" value="' . __('Update') . '"></form></td>';
		print '</tr>';
	}

	html_end_box();
}

function weathermap_map_settings($id) {
    global $manager;

    if ($id == 0) {
        $title = __('Additional settings for ALL maps');
        $nonemsg = __('There are no settings for all maps yet. You can add some by clicking Add up in the top-right, or choose a single map from the management screen to add settings for that map.');
        $type = 'global';
        $settingrows = $manager->getMapSettings(0);
    } elseif ($id < 0) {
        $group_id = -intval($id);
        $group = $manager->getGroup($group_id);

        $title = __('Edit per-map settings for Group %s: %s', $group->id, $group->name);
        $nonemsg = __('There are no per-group settings for this group yet. You can add some by clicking Add up in the top-right.');
        $type = 'group';
        $settingrows = $manager->getMapSettings(-$group_id);

        print '<p>' . __('All maps in this group are also affected by the following GLOBAL settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):') . '</p>';
        weathermap_readonly_settings(0, __('Global Settings'));

    } else {
        $map = $manager->getMap($id);
        $group = $manager->getGroup($map->group_id);

        $title = __('Edit per-map settings for Weathermap %s: %s', $id, $map->titlecache);
        $nonemsg = __('There are no per-map settings for this map yet. You can add some by clicking Add up in the top-right.');
        $type = 'map';
        $settingrows = $manager->getMapSettings(intval($id));

        print '<p>' . __('This map is also affected by the following GLOBAL and GROUP settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):') . '</p>';

        weathermap_readonly_settings(0, __('Global Settings'));

        weathermap_readonly_settings(-$map->group_id, __('Group Settings (%s)', htmlspecialchars($group->name)));
    }

    html_start_box($title, '100%', '', '2', 'center', 'weathermap-cacti10-plugin-mgmt.php?action=map_settings_form&mapid=' . intval($id));
    html_header(array(__('Actions'), __('Name'), __('Value')), 2);

    if (is_array($settingrows)) {
        if (sizeof($settingrows) > 0) {
            foreach ($settingrows as $setting) {
                form_alternate_row();
                print '<td style="width:4%"><a href="weathermap-cacti10-plugin-mgmt.php?action=map_settings_form&mapid=' . $id . '&id=' . intval($setting->id) . '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="' . __('Edit this definition') . '">' . __('Edit') . '</a></td>';
                print "<td>" . htmlspecialchars($setting->optname) . "</td>";
                print "<td>" . htmlspecialchars($setting->optvalue) . "</td>";
                print '<td><a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=map_settings_delete&header=false&mapid=' . $id . '&id=' . intval($setting->id) . '"><img src="../../images/delete_icon_large.gif" width="12" height="12" border="0" alt="' . __('Remove this definition from this map') . '"></a></td>';
                print "</tr>";
            }
        } else {
            print '<tr>';
            print "<td colspan=2><em>$nonemsg</em></td>";
            print '</tr>';
        }
    }

    html_end_box();

    print '<div align=center>';

    if ($type == 'group') {
        print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=groupadmin">' . __('Back to Group Admin') . '</a>';
    }

    if ($type == 'global') {
        print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=">' . __('Back to Map Admin') . '</a>';
    }

    print '</div>';
}

function weathermap_readonly_settings($id, $title = '') {
    global $manager;

	if ($title == '') {
		$title = __('Settings');
	}

    if ($id == 0) {
        $settings = $manager->getMapSettings(0);
    }

    if ($id < 0) {
        $settings = $manager->getMapSettings(-intval($id));
    }

    if ($id > 0) {
        $settings = $manager->getMapSettings(intval($id));
    }

    html_start_box($title, '100%', '', '2', 'center', '');
    html_header(array(__('Name'), __('Value')));

    if (sizeof($settings) > 0) {
        foreach ($settings as $setting) {
            form_alternate_row();
            print '<td>' . htmlspecialchars($setting->optname) . '</td>';
			print '<td>' . htmlspecialchars($setting->optvalue) . '</td>';
            print '</tr>';
        }
    } else {
        form_alternate_row();
        print '<td colspan=4><em>' . __('No Settings') . '</em></td>';
        print '</tr>';
    }

    html_end_box();
}

function weathermap_map_settings_form($mapId = 0, $settingId = 0) {
    global $config;
    global $manager;

    if ($mapId < 0) {
        $item = $manager->getGroup(-$mapId);
        $title = $item->name;
    }
    if ($mapId > 0) {
        $item = $manager->getMap($mapId);
        $title = $item->titlecache;
    }

    $name = '';
    $value = '';

    if ($settingId != 0) {

        $setting = $manager->getMapSettingById($settingId);

        if ($setting !== false) {
            $name = $setting->optname;
            $value = $setting->optvalue;
        }
    }

    # print '$mapid $settingid |$name| |$value|';

    $values_ar = array();

    $field_ar = array(
        'name' => array(
            'friendly_name' => __('Name'),
            'method' => 'textbox',
            'max_length' => 128,
            'description' => __('The name of the map-global SET variable'),
            'value' => $name
        ),
        'value' => array(
            'friendly_name' => __('Value'),
            'method' => 'textbox',
            'max_length' => 128,
            'description' => __('What to set it to'),
            'value' => $value
        ),
        'mapid' => array(
			'friendly_name' => __('Map ID'), 
			'method' => 'hidden_zero', 
			'value' => $mapId
		),
        'id' => array(
			'friendly_name' => __('Setting ID'), 
			'method' => 'hidden_zero', 
			'value' => $settingId
		)
    );

    if ($mapId == 0) {
		if ($settingId) {
        	$title = __('Edit setting for ALL maps');
		}else{
        	$title = __('Create setting for ALL maps');
		}
    } elseif ($mapId < 0) {
		$groupId = -$mapId;
		if ($settingId) {
	        $title = __('Edit per-group setting for Group %s: %s', $groupId, $title);
		}else{
			$title = __('Create per-group setting for Group %s: %s', $groupId, $title);
		}
    } else {
		if ($settingId) {
        	$title = __('Edit per-map setting for Weathermap %s: %s', $mapId, $title);
		}else{
        	$title = __('Create per-map setting for Weathermap %s: %s', $mapId, $title);
		}
    }

	form_start('weathermap-cacti10-plugin-mgmt.php');

    html_start_box($title, '100%', '', '3', 'center', '');

    draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($field_ar, (isset($values_ar) ? $values_ar : array()))
		)
	);

    html_end_box();

    form_save_button('weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=' . $mapId);
}

function weathermap_chgroup($id) {
    global $manager;

    $map      = $manager->getMap($id);
    $title    = $map->titlecache;
    $curgroup = $map->group_id;

	form_start('weathermap-cacti10-plugin-mgmt.php', 'editme');

    html_start_box(__('Edit map group for Weathermap %s: %s', $id, $title), '100%', '', '2', 'center', '');

    print "<td>" . __('Choose an existing Group:') . "&nbsp;<select name='new_group'>";

    $groups = $manager->getGroups();

    foreach ($groups as $grp) {
        print '<option ';
        if ($grp->id == $curgroup) print ' SELECTED ';
        print 'value=' . $grp->id . '>' . htmlspecialchars($grp->name) . '</option>';
    }

    print '</select>';
    print "&nbsp;<input type='button' id='save' name='save' value='" . __('Save') . "' title='" . __('Change Group') . "'>";
    print '</td>';
    print "</tr>\n";
    print '<tr><td></td></tr>';

    print "<tr><td><p>" . __('or create a new group in the <b><a href=\'weathermap-cacti10-plugin-mgmt.php?action=groupadmin\'>group management screen</a>') . "</b></p></td></tr>";

    print "<tr><td><input type=hidden name='map_id' value='" . $id . "'></td></td>";
    print "<tr><td><input type=hidden name='action' value='chgroup_update'></td></td>";

    html_end_box();

	form_end();

	?>
	<script type='text/javascript'>
	$(function() {
		$('#save').click(function() {
			strURL = 'weathermap-cacti10-plugin-mgmt.php';
			strURL += (strURL.indexOf('?') >= 0 ? '&':'?') + 'header=false';
			json =  $('#editme').serializeObject();
				$.post(strURL, json).done(function(data) {
					$('#main').html(data);
					applySkin();
					window.scrollTo(0, 0);
				});
		});
	});
	</script>
	<?php
}

function weathermap_group_form($id = 0) {
    global $config;
    global $manager;

	form_start('weathermap-cacti10-plugin-mgmt.php');

    $groupName = '';
    // if id==0, it's an Add, otherwise it's an editor.
    if ($id == 0) {
    	html_start_box(__('Adding a Group...'), '100%', '', '2', 'center', '');
    } else {
    	html_start_box(__('Editing Group %s', $id), '100%', '', '2', 'center', '');
        $group = $manager->getGroup($id);
        $groupName = $group->name;
    }

    print '<td>' . __('Group Name:') . "<input type='text' name='gname' value='" . htmlspecialchars($groupName) . "'/>\n";

    if ($id > 0) {
        print " <input type='submit' value='" . __('Update') . "' /></td>\n";
    } else {
        print " <input type='submit' value='" . __('Add') . "' /></td>\n";
    }

    print "<td><input type='hidden' name='action' value='group_update'/></td>";
	if ($id > 0) {
		print "<td><input type='hidden' name='id' value='$id' /></td>\n";
	}

	html_end_box();

	form_end();

	//print "<script type='text/javascript'>$(function() { applySkin() }</script>\n";
}

function weathermap_group_editor() {
    global $config;
    global $manager;

    html_start_box(__('Edit Map Groups'), '100%', '', '2', 'center', 'weathermap-cacti10-plugin-mgmt.php?action=group_form&id=0');
    html_header(array(__('Actions'), __('Group Name'), __('Settings'), __('Sort Order')), 2);

    $groups = $manager->getGroups();

    if (is_array($groups)) {
        if (sizeof($groups) > 0) {
            foreach ($groups as $group) {
                form_alternate_row();
                print '<td style="width:4%"><a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=group_form&id=' . intval($group->id) . '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="" title="' . __('Rename This Group') . '"></a></td>';
                print '<td>' . htmlspecialchars($group->name) . '</td>';

                print '<td>';
                print "<a class='hyperLink' href='weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=-" . $group->id . "'>";
                $setting_count = $manager->getMapSettingCount(0, $group->id);
                if ($setting_count > 0) {
                    print __n('%s special', '%s specials', $setting_count);
                } else {
                    print __('standard');
                }
                print '</a>';
                print '</td>';

                print '<td>';
                print '<span class="remover fa fa-caret-up moveArrow" href="weathermap-cacti10-plugin-mgmt.php?action=move_group_up&id=' . $group->id . '" title="' . __('Move Group Up') . '"></span>';
                print '<span class="remover fa fa-caret-down moveArrow" href="weathermap-cacti10-plugin-mgmt.php?action=move_group_down&id=' . $group->id . '" title="' . __('Move Group Down') . '"></span>';
                print '</td>';

                print '<td class="right">';
                if ($group->id > 1) {
                    print '<span class="remover fa fa-remove deleteMarker" href="weathermap-cacti10-plugin-mgmt.php?action=groupadmin_delete&id=' . intval($group->id) . '" title="' . __('Remove this definition from this map') . '"></span>';
                }
                print '</td>';

                print '</tr>';
            }
        } else {
            print '<tr>';
            print '<td colspan=2>' . __('No groups are defined.') . '</td>';
            print '</tr>';
        }
    }

    html_end_box();

	?>
	<script type='text/javascript'>
	$(function() {
		$('.remover').click(function() {
			href=$(this).attr('href');
			loadPageNoHeader(href);
		});
	});
	</script>
	<?php
}

function weathermap_group_create($newname) {
    global $manager;

    $manager->createGroup($newname);
}

function weathermap_group_update($id, $newname) {
    global $manager;

    $manager->renameGroup($id, $newname);
}

function weathermap_group_delete($id) {
    global $manager;

    $manager->deleteGroup($id);
}

function weathermap_setting_save($mapid, $name, $value) {
    global $manager;

    $manager->saveMapSetting($mapid, $name, $value);
}

function weathermap_setting_update($mapid, $settingid, $name, $value) {
    global $manager;

    $manager->updateMapSetting($settingid, $name, $value);
}

function weathermap_setting_delete($mapid, $settingid) {
    global $manager;

    $manager->deleteMapSetting($mapid, $settingid);
}

function map_deactivate($id) {
    global $manager;

    $manager->disableMap($id);
}

function map_activate($id) {
    global $manager;

    $manager->activateMap($id);
}

function map_delete($id) {
    global $manager;

    $manager->deleteMap($id);
}

function weathermap_set_group($mapid, $groupid) {
    global $manager;

    $manager->setMapGroup($mapid, $groupid);
}

function perms_add_user($mapid, $userid) {
    global $manager;
    $manager->addPermission($mapid, $userid);
}

function perms_delete_user($mapid, $userid) {
    global $manager;
    $manager->removePermission($mapid, $userid);
}

// Repair the sort order column (for when something is deleted or inserted, or moved between groups)
// our primary concern is to make the sort order consistent, rather than any special 'correctness'
function map_resort() {
    global $manager;

    $manager->resortMaps();
}

// Repair the sort order column (for when something is deleted or inserted)
function weathermap_group_resort() {
    global $manager;

    $manager->resortGroups();
}

function map_move($mapid, $junk, $direction) {
    global $manager;

    $manager->moveMap($mapid, $direction);
}

function weathermap_group_move($id, $direction) {
    global $manager;

    $manager->moveGroup($id, $direction);
}

function add_config($file) {
    global $weathermap_confdir;
    global $manager;

    $manager->addMap($file);
}

// vim:ts=4:sw=4:
