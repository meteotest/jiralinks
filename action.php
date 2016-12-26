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
	 * Flag to detect double triggering of the event
	 * 
	 * @var bool
	 */
	protected $alreadyTriggered = FALSE;

	/**
	 * Array stored old state
	 * 
	 * @var array
	 */
	protected $oldKeys = array("");
	
	/**
	 * Register the COMMON_WIKIPAGE_SAVE event handler, if required
	 * 
	 * @param Doku_Event_Handler $controller
	 */
	public function register(Doku_Event_Handler &$controller) {
		if($this->getConf('enable_adding_urls_to_issues') and function_exists('curl_version')) {
			$controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'addRemoteIssueLinks');
		}
		
	}
	
	/**
	 * Save old keys of page
	 * 
	 * @param array $oldKeys
	 */
	public function saveOldKeys($oldKeys) {
		// Look for issue keys
		if(preg_match_all('/[A-Z]+?-[0-9]+/', $oldKeys, $keys)) {
			$this->oldKeys = array_unique($keys[0]);
			$this->oldKeys = $this->filterExistingIssues($this->oldKeys);
		}
	}


	/**
	 * Filter existing issues
	 * 
	 * @param array $keys 
	 * @return array
	 */
	public function filterExistingIssues($keys) {
		foreach($keys as $key)
		{
			$response = $this->executeRequest("issue/{$key}", 'GET');
			if(!$response->id)
			{
				array_splice($keys, array_search($key, $keys), 1);
			}
		}
		return $keys;
	}
	
	/**
	 * Add remote issue links
	 *  
	 * @param Doku_Event $event
	 * @param mixed $param
	 */
	public function addRemoteIssueLinks(Doku_Event &$event, $param) {
		if($this->alreadyTriggered) return;
		
		global $ID, $INFO, $conf;	
		
		$this->saveOldKeys($event->data[oldContent]);
		
		// Look for issue keys
		if(preg_match_all('/[A-Z]+?-[0-9]+/', $event->data[newContent], $keys)) {
			
			// Keys found, prepare data for the remote issue link
			$keys = array_unique($keys[0]);
			
			$keys = $this->filterExistingIssues($keys);
			
			$url = wl($ID, '', TRUE);
			$globalId = md5($url); // MD5 hash is used because the global id max length is 255 characters. An effective page URL might be longer.
			$applicationName =  $conf['title'];
			$applicationType = 'org.dokuwiki';
			$title = $applicationName . ' - ' . (empty($INFO['meta']['title']) ? $event->data[2] : $INFO['meta']['title']);
			$relationship = $this->getConf('url_relationship');
			$favicon = tpl_getMediaFile(array(':wiki:favicon.ico', ':favicon.ico', 'images/favicon.ico'), TRUE);

			foreach($keys as $key) {
				// Prepare final data array
				$data = array(
					'globalId' => $globalId,
					'application' => array(
							'type' => $applicationType,
							'name' => $applicationName,
							),
					'relationship' => $relationship,
					'object' => array(
							'url' => $url,
							'title' => $title,
							'icon' => array(
									'url16x16' => $favicon),
							),
				);
				
				$this->executeRequest("issue/{$key}/remotelink", 'POST', $data);

			}
			
			$this->alreadyTriggered = TRUE;
		}
	}
	
	/*
	 * Execute request
	 * 
	 * @param string $request
	 * @param string $method
	 * @param array $data
	 * @return StdClass
	 */
	protected function executeRequest($request, $method = 'GET', $data = NULL) {
		$curl = curl_init($this->getConf('jira_api_url') . $request);

		// Additional curl setup
		switch(strtoupper($method)) {
			default: break;
			case 'GET':
				// Do nothing
				break;
			case 'POST':
				curl_setopt($curl, CURLOPT_POST, TRUE);
				
				// Send data
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
		
		// Basic curl setup
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC) ;
		curl_setopt($curl, CURLOPT_USERPWD, $this->getConf('jira_api_username') . ':' . $this->getConf('jira_api_password'));
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		
		// Execution
		$response = curl_exec($curl);
		curl_close($curl);
		
		return json_decode($response);
	}
}
