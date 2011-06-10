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


abstract class InputTemplate
{
	public
		$LogName,
		$pOutputTemplate;
	
	
	private
		$sServiceName = "(none)",
		$pOutputObject = null;
	
	
	/**
	 *	Called when the Template is initalised.
	 */
	public final function __construct($sLogName, $pOutputTemplate)
	{
		$this->LogName = $sLogName;
		
		$this->pOutputTemplate = $pOutputTemplate;
		$this->pOutputTemplate->pInputTemplate = $this;
		
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
	public final function setServiceName($sServiceName)
	{
		$this->sServiceName = $sServiceName;
	}
	
	
	/**
	 *	Retrieve the service name.
	 */
	public final function getServiceName()
	{
		return $this->sServiceName;
	}
	
	
	/**
	 *	Returns template name.
	 */
	public static function getTemplateName()
	{
		$sClassName = get_called_class();
		
		return substr($sClassName, 0, -5);
	}

	
	
	/**
	 *	This is called when the Template is initialised.
	 */
	abstract public function onConstruct();
	
	
	/**
	 *	Call the events in the output template.
	 */
	public final function __call($sMethod, $aArguments)
	{
		$aArguments[] = "";
		
		if(!method_exists($this->pOutputTemplate, $sMethod))
		{
			return null;
		}
		
		return $this->pOutputTemplate->$sMethod($aArguments[0]);
	}
}
