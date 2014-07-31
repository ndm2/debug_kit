<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace DebugKit\Panel;

use Cake\Controller\Controller;
use Cake\Log\Log;
use DebugKit\DebugPanel;

/**
 * Log Panel - Reads log entries made this request.
 *
 */
class LogPanel extends DebugPanel {

/**
 * Constructor - sets up the log listener.
 *
 * @return \LogPanel
 */
	public function __construct() {
		parent::__construct();
		Log::config('debug_kit_log_panel', array(
			'engine' => 'DebugKit.DebugKit',
		));
	}

/**
 * beforeRender Callback
 *
 * @param Controller $controller
 * @return array
 */
	public function beforeRender(Controller $controller) {
		return Log::engine('debug_kit_log_panel');
	}
}
