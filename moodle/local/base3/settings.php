<?php declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
	$ADMIN->add('localplugins', new admin_category('local_base3_category', new lang_string('pluginname', 'local_base3')));
	$ADMIN->add('local_base3_category', new admin_externalpage(
		'local_base3_modules', new lang_string('modules', 'local_base3'),
		new moodle_url('/local/base3/admin.php')
	));
	$ADMIN->add('local_base3_category', new admin_externalpage(
		'local_base3_tools', new lang_string('tools', 'local_base3'),
		new moodle_url('/local/base3/tools.php')
	));
	$global = new admin_settingpage('local_base3_global', new lang_string('globalembed', 'local_base3'));
	if ($ADMIN->fulltree) {
		$global->add(new admin_setting_configtext(
		'local_base3/globaldisplay', new lang_string('globaldisplay', 'local_base3'),
		new lang_string('globaldisplaydesc', 'local_base3'), '', PARAM_ALPHANUMEXT
	));
	$global->add(new admin_setting_configtextarea(
		'local_base3/globaldata', new lang_string('globaldata', 'local_base3'),
		new lang_string('globaldatadesc', 'local_base3'), '{}', PARAM_RAW
	));
	}
	$ADMIN->add('local_base3_category', $global);

	// Native Moodle settings control visibility only. Launcher presentation is
	// configured centrally under BASE3 Tools > Chatbot > Launcher.
	$bubble = new admin_settingpage('local_base3_chatbotbubble', new lang_string('chatbotbubble', 'local_base3'));
	if ($ADMIN->fulltree) {
		$bubble->add(new admin_setting_configcheckbox(
			'local_base3/chatbotbubble_enabled', new lang_string('chatbotbubble_enabled', 'local_base3'),
			new lang_string('chatbotbubble_enableddesc', 'local_base3'), '0'
		));
		$bubble->add(new admin_setting_configselect(
			'local_base3/chatbotbubble_visibility', new lang_string('chatbotbubble_visibility', 'local_base3'),
			new lang_string('chatbotbubble_visibilitydesc', 'local_base3'), 'authenticated', [
				'all' => new lang_string('chatbotbubble_all', 'local_base3'),
				'authenticated' => new lang_string('chatbotbubble_authenticated', 'local_base3'),
				'administrators' => new lang_string('chatbotbubble_administrators', 'local_base3')
			]
		));
	}
	$ADMIN->add('local_base3_category', $bubble);
}
