<?php
/**
 *	Adium output template for Log Converter.
 *
 *	Author:		David Weston <westie@typefish.co.uk>
 *
 *	Version:        <version>
 *	Git commit:     <commitHash>
 *	Committed at:   <commitTime>
 *
 *	Licence:	http://www.typefish.co.uk/licences/
 */


class stdoutOutput extends OutputTemplate
{
	private
		$pXML = null,
		$pSession = null;
	
	
	/**
	 *	Called when the Template is loaded.
	 */
	public function onConstruct()
	{
	}
	
	
	/**
	 *	Called when the session begins.
	 */
	public function sessionBegin($pObject)
	{
		print(__FUNCTION__."():");
		Core::println(print_r($pObject, true));
	}
	
	
	/**
	 *	Called when the session ends.
	 */
	public function sessionEnd()
	{
		println(__FUNCTION__."():");
	}
	
	
	/**
	 *	Called when a user's status changes. It may be to whatever,
	 *	offline, online, etc.
	 */
	public function userChangedStatus($pObject)
	{
		print(__FUNCTION__."():");
		Core::println(print_r($pObject, true));
	}
	
	
	/**
	 *	Called when a user changes their status, or rather, personal message.
	 */
	public function userChangedStatusMessage($pObject)
	{
		print(__FUNCTION__."():");
		Core::println(print_r($pObject, true));
	}
	
	
	/**
	 *	Called when a user changes their nickname.
	 */
	public function userChangedNickname($pObject)
	{	
		print(__FUNCTION__."():");
		Core::println(print_r($pObject, true));
	}
	
	
	/**
	 *	Called when a user sends an alert.
	 */
	public function userSentAlert($pObject)
	{
		print(__FUNCTION__."():");
		Core::println(print_r($pObject, true));
	}
	
	
	/**
	 *	Called when a user sends a message.
	 */
	public function userSentMessage($pObject)
	{
		print(__FUNCTION__."():");
		Core::println(print_r($pObject, true));
	}
}