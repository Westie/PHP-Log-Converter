<?php
/**
 *	The input template class for Log Converter.
 *
 *	Author:		David Weston <westie@typefish.co.uk>
 *
 *	Version:        <version>
 *	Git commit:     <commitHash>
 *	Committed at:   <commitTime>
 *
 *	Licence:	http://www.typefish.co.uk/licences/
 */


abstract class OutputTemplate
{
	public
		$LogName,
		$pInputTemplate;
	
	
	private
		$sServiceName = "(none)",
		$pOutputObject = null;
	
	
	/**
	 *	Called when the Template is initalised.
	 */
	public final function __construct()
	{
		return $this->onConstruct();
	}
	
	
	/**
	 *	Called when the Template is removed.
	 */
	public final function __destruct()
	{
		return true;
	}
	
	
	/**
	 *	Returns the log file as a string.
	 */
	public final function getLogString()
	{
		return file_get_contents($this->LogName);
	}
	
	
	/**
	 *	Returns the log file as an array.
	 */
	public final function getLogArray()
	{
		return file($this->LogName);
	}
	
	
	/**
	 *	Set the service name.
	 */
	public final function getServiceName($sServiceName)
	{
		return $this->pInputTemplate->getServiceName();
	}
	
	
	/**
	 *	Returns template name.
	 */
	public static function getTemplateName()
	{
		$sClassName = get_called_class();
		
		return substr($sClassName, 0, -6);
	}
	
	
	/**
	 *	Returns the template directory.
	 */
	public static function getTemplateOutputDirectory()
	{
		$sClassName = get_called_class();
		$sDirectory = SYSTEM.'/Resources/Output/'.substr($sClassName, 0, -6).'/';
		
		if(!is_dir($sDirectory))
		{
			mkdir($sDirectory, 0777, true);
		}
		
		return $sDirectory;
	}
	
	
	
	/**
	 *	This is called when the Template is initialised.
	 */
	abstract public function onConstruct();
	
	
	/**
	 *	Called when the session begins.
	 */
	public function sessionBegin($pObject)
	{
	}
	
	
	/**
	 *	Called when the session ends.
	 */
	public function sessionEnd()
	{
	}
	
	
	/**
	 *	Called when a user's status changes. It may be to whatever,
	 *	offline, online, etc.
	 */
	public function userChangedStatus($pObject)
	{
	}
	
	
	/**
	 *	Called when a user changes their status, or rather, personal message.
	 */
	public function userChangedStatusMessage($pObject)
	{
	}
	
	
	/**
	 *	Called when a user changes their nickname.
	 */
	public function userChangedNickname($pObject)
	{
	}
	
	
	/**
	 *	Called when a user sends an alert.
	 */
	public function userSentAlert($pObject)
	{
	}
	
	
	/**
	 *	Called when a user sends a message.
	 */
	public function userSentMessage($pObject)
	{
	}
}
