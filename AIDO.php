<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Date:   Wednesday, July 18th 2018
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


namespace system\packages\aido;

use \system\classes\Core;
use \system\classes\Utils;
use \system\classes\Database;
use \system\classes\Configuration;
use \system\packages\duckietown\Duckietown;

/**
*   Utility functions for all the AIDO packages
*/
class AIDO{

	private static $initialized = false;
	private static $challenges_api_protocol = "https";
	private static $challenges_api_host = "challenges.duckietown.org";
	private static $challenges_api_version = "v2";

	// disable the constructor
	private function __construct() {}

	/** Initializes the module.
     *
     *	@retval array
	 *		a status array of the form
	 *	<pre><code class="php">[
	 *		"success" => boolean, 	// whether the function succeded
	 *		"data" => mixed 		// error message or NULL
	 *	]</code></pre>
	 *		where, the `success` field indicates whether the function succeded.
	 *		The `data` field contains errors when `success` is `FALSE`.
     */
	public static function init(){
		if( !self::$initialized ){
			// register style
			Core::registerCSSstylesheet( 'aido.css', 'aido' );
			//
			self::$initialized = true;
			return ['success' => true, 'data' => null];
		}else{
			return ['success' => true, 'data' => "Module already initialized!"];
		}
	}//init

	/** Returns whether the module is initialized.
     *
     *	@retval boolean
	 *		whether the module is initialized.
     */
	public static function isInitialized(){
		return self::$initialized;
	}//isInitialized

    /** Safely terminates the module.
     *
     *	@retval array
	 *		a status array of the form
	 *	<pre><code class="php">[
	 *		"success" => boolean, 	// whether the function succeded
	 *		"data" => mixed 		// error message or NULL
	 *	]</code></pre>
	 *		where, the `success` field indicates whether the function succeded.
	 *		The `data` field contains errors when `success` is `FALSE`.
     */
	public static function close(){
		// do stuff
		return [ 'success' => true, 'data' => null ];
	}//close



	// =======================================================================================================
	// Public functions

	public static function getSubmissionsStatusList(){
		return [
			'submitted',
			'success',
			'failed',
			'evaluating',
			'retired',
			'aborted',
			'error'
		];
	}//getSubmissionsStatusList

	public static function getSubmissionsStatusStyle( $status ){
		$icon = '';
		$color = '';
		switch( $status ){
			case 'submitted':
				$icon = 'clock-o';
				$color = 'black';
				break;
			case 'success':
				$icon = 'check';
				$color = 'green';
				break;
			case 'failed':
				$icon = 'exclamation-circle';
				$color = 'red';
				break;
			case 'evaluating':
				$icon = 'refresh';
				$color = '#337ab7';
				break;
			case 'retired':
				$icon = 'trash';
				$color = 'black';
				break;
			case 'aborted':
				$icon = 'hand-paper-o';
				$color = 'red';
				break;
			case 'error':
				$icon = 'bug';
				$color = 'black';
				break;
			default: break;
		}
		return ['icon' => $icon, 'color' => $color];
	}//getSubmissionsStatusStyle

	public static function callChallengesAPI( $method, $service, $action=null, $data=[], $headers=[], $user_id=null ){
		if( !in_array($method, ['GET', 'POST', 'DELETE']) )
			return ['success'=>false, 'data'=>sprintf('Method `%s` not supported', $method)];
		// get duckietoken
		$token = Duckietown::getUserToken();
		if( !is_null($user_id) ){
			$token = Duckietown::getUserToken($user_id);
		}
		// build querystring
		$querystring = '';
		if( $method == 'GET' )
			$querystring = toQueryString( array_keys($data), $data, true/*questionMarkAppend*/ );
		// build url
		$url = sprintf(
			'%s://%s/%s/%s%s%s',
			self::$challenges_api_protocol,
			self::$challenges_api_host,
			self::$challenges_api_version,
			$service,
			is_null($action)? '' : sprintf('/%s', $action),
			$querystring
		);
		// configure a CURL object
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		if( $method == 'POST' )
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
		if( !is_null($token) )
			array_push($headers, sprintf('X-Messaging-Token: %s', $token));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		// call CURL
		$curl_response = curl_exec($curl);
		$curl_res = curl_getinfo($curl);
		curl_close($curl);
		// handle errors
		if( $curl_response === false || $curl_res['http_code'] != 200 ){
			return [
				'success'=>false,
				'data'=>sprintf(
					'An error occurred while talking to the challenges API. The server returned the code <strong>%d</strong>.',
					$curl_res['http_code']
				)
			];
		}
		// get answer
		$decoded = json_decode($curl_response, true);
		if( isset($decoded['ok']) && $decoded['ok'] === false ){
			return [
				'success'=>false,
				'data'=>sprintf(
					'An error occurred while talking to the challenges API. The server reports: "%s"',
					$decoded['msg']
				)
			];
		}
		// success
		return [ 'success'=>true, 'data'=>$decoded['result'], 'total'=>$decoded['total'] ];
	}//callChallengesAPI



	// =======================================================================================================
	// Private functions

	// YOUR PRIVATE METHODS HERE

}//AIDO
?>
