<?php declare(strict_types=1);

final class block_base3_edit_form extends block_edit_form {

	protected function specific_definition($mform): void {
		$mform->addElement('header', 'config_header', get_string('settings', 'block_base3'));
		$mform->addElement('text', 'config_title', get_string('title', 'block_base3'));
		$mform->setType('config_title', PARAM_TEXT);
		$mform->addElement('text', 'config_name', get_string('displayname', 'block_base3'));
		$mform->setType('config_name', PARAM_ALPHANUMEXT);
		$mform->addRule('config_name', null, 'required', null, 'client');
		$mform->addElement('textarea', 'config_data', get_string('displaydata', 'block_base3'), ['rows' => 5, 'cols' => 65]);
		$mform->setType('config_data', PARAM_RAW);
	}
}
