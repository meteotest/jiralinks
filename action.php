<?php
/**
 * Jira-links action plugin for DokuWiki
 *
 * This action adds Jira remote issue links when saving a page.
 *
 * @author christian studer <christian.studer@meteotest.ch>
 * @license GPL2 http://www.gnu.org/licenses/gpl-2.0.html
 */

// Must be run within DokuWiki
if (!defined('DOKU_INC')) die();

/**
 * The Jira-links action plugin itself
 * 
 * @author christian studer <christian.studer@meteotest.ch>
 */
class action_plugin_jiralinks extends DokuWiki_Action_Plugin {
	/**
	 * Register the IO_WIKIPAGE_SAVE AFTER event handler
	 * 
	 * @param Doku_Event_Handler $controller
	 */
	public function register(Doku_Event_Handler &$controller) {
		$controller->register_hook('IO_WIKIPAGE_SAVE', 'AFTER', $this, 'addRemoteIssueLinks');
	}
	
	/**
	 * Add remote issue links
	 *  
	 * @param Doku_Event $event
	 * @param mixed $param
	 */
	public function addRemoteIssueLinks(Doku_Event &$event, $param) {
		// TODO implement this
	}
}
