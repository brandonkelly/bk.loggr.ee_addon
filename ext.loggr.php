<?php

// prevent direct access to this file
if ( ! defined('EXT')) exit('Invalid file request');

class Loggr {

	var $name           = 'Loggr';
	var $version        = '0.0.5';
	var $settings_exist = 'n';

	var $hooks = array(
		'sessions_start' => array('priority' => '1'),
		'show_full_control_panel_end' => array('priority' => '99')
	);
	var $hooks_changed = '0.0.5';

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		// add hooks
		$this->reset_hooks();
	}

	/**
	 * Update Extension
	 */
	function update_extension($current='')
	{
		global $DB;

		if ( ! $current OR $current == $this->version)
			return FALSE;

		if (version_compare($current, $this->hooks_changed, '<'))
			$this->reset_hooks();
		else
			$DB->query('UPDATE exp_extensions SET version = "'.$this->version.'" WHERE class = "'.get_class($this).'"');
	}

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		global $DB;

		$DB->query($DB->update_string('exp_extensions', array('enabled' => 'n'), 'class = "'.get_class($this).'"'));
	}

	/**
	 * Reset Hooks
	 *
	 * Removes all of this extension's hooks and replaces them with the new lineup
	 */
	function reset_hooks()
	{
		global $DB;

		// delete old hooks
		$DB->query('DELETE FROM exp_extensions
		              WHERE class = "'.get_class($this).'"');

		// add new hooks
		$hook_tmpl = array(
			'class'    => get_class($this),
			'settings' => '',
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		);

		foreach($this->hooks as $hook => $data)
		{
			if (is_string($data))
			{
				$hook = $data;
				$data = array();
			}
			$data = array_merge($hook_tmpl, array('hook' => $hook, 'method' => $hook), $data);
			$DB->query($DB->insert_string('exp_extensions', $data));
		}
	}

	/**
	 * Get Last Call
	 *
	 * Retrieves the current hook's last-returned value, and overwrites the $param with it
	 *
	 * @param  mixed  &$param  Reference to parameter sent by extension hook
	 * @access private
	 */
	function get_last_call(&$param, $default = NULL)
	{
		global $EXT;

		if ($EXT->last_call !== FALSE)
			$param = $EXT->last_call;
		else if ($param !== NULL && $default !== NULL)
			$param = $default;
	}

	function log()
	{
		global $SESS;
		if ( ! isset($SESS->cache['loggr'])) $SESS->cache['loggr'] = array();
		$SESS->cache['loggr'][] = func_get_args();
	}

	function show_full_control_panel_end($out)
	{
		global $SESS, $DSP;

		$this->get_last_call($out, '');

		if (isset($SESS->cache['loggr']))
		{
			$out .= '<script type="text/javascript">'.NL;
			foreach($SESS->cache['loggr'] as $msgs)
			{
				$out .= 'console.log(';
				foreach($msgs as $i => $msg)
				{
					if ($i > 0) $out .= ', ';
					$out .= json_encode($msg);
				}
				$out .= ');'.NL;
			}
			$out .= '</script>'.NL;
		}

		return $out;
	}
}
