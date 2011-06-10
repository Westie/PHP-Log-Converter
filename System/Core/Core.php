<?php
/**
 *	The core class for Log Converter.
 *
 *	Author:		David Weston <westie@typefish.co.uk>
 *
 *	Version:        <version>
 *	Git commit:     <commitHash>
 *	Committed at:   <commitTime>
 *
 *	Licence:	http://www.typefish.co.uk/licences/
 */


class Core
{
	public static
		$aMembers = null;
	
	
	/**
	 *	Prints an output.
	 */
	public static function println($sMessage)
	{
		echo ' '.$sMessage.PHP_EOL;
	}
	
	
	/**
	 *	Incorporate a template into the Log Converter.
	 */
	public static function incorporateTemplate($sTemplateName)
	{
		if(file_exists(SYSTEM."/Templates/{$sTemplateName}/Input.php"))
		{
			include SYSTEM."/Templates/{$sTemplateName}/Input.php";
		}
		
		if(file_exists(SYSTEM."/Templates/{$sTemplateName}/Output.php"))
		{
			include SYSTEM."/Templates/{$sTemplateName}/Output.php";
		}
	}
	
	
	/**
	 *	Convert from one format to another format.
	 */
	public static function convertLogs($sLogResource, $sOriginalFormat, $sOutputFormat)
	{
		$sOriginalClass = "{$sOriginalFormat}Input";
		$sOutputClass = "{$sOutputFormat}Output";
		
		if(!class_exists($sOriginalClass))
		{
			Core::println("Error: The template file for {$sOriginalFormat} doesn't exist, or isn't loaded.");
			exit;
		}
		
		if(!class_exists($sOutputClass))
		{
			Core::println("Error: The template class for {$sOutputFormat} doesn't exist, or isn't loaded.");
			exit;
		}
		
		$aLogResources = glob(SYSTEM."/Resources/Input/{$sOriginalFormat}/{$sLogResource}");
		
		foreach($aLogResources as $sLogResource)
		{
			new $sOriginalClass($sLogResource, new $sOutputClass());
		}
	}
	
	
	/**
	 *	Sets environment variables to both the input and output templates
	 */
	public static function __callStatic($sTemplateName, $aArguments)
	{
		if(count($aArguments) == 1)
		{
			if(isset(self::$aMembers->{$sTemplateName}->{$aArguments[0]}))
			{
				return self::$aMembers->{$sTemplateName}->{$aArguments[0]};
			}
			
			return null;
		}
		
		list($sMemberKey, $mMemberValue) = $aArguments;
		
		if(self::$aMembers == null)
		{
			self::$aMembers = new stdClass();
		}
		
		if(!isset(self::$aMembers->{$sTemplateName}))
		{
			self::$aMembers->{$sTemplateName} = new stdClass();
		}
		
		self::$aMembers->{$sTemplateName}->{$sMemberKey} = $mMemberValue;
	}
}