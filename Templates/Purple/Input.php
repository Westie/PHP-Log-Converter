<?php
/**
 *	libpurple input template for Log Converter.
 *
 *	Author:		David Weston <westie@typefish.co.uk>
 *
 *	Version:        <version>
 *	Git commit:     <commitHash>
 *	Committed at:   <commitTime>
 *
 *	Licence:	http://www.typefish.co.uk/licences/
 */


class PurpleInput extends InputTemplate
{
	private
		$iSessionTime = 0,
		$aUsers = array(),
		$aEvents = array(),
		$aMessages = array();
	
	
	/**
	 *	Called when the Template is loaded.
	 */
	public function onConstruct()
	{
		$aSelfUser = Core::Pidgin("selfUser");
		
		foreach($aSelfUser as $sAddress => $sNickname)
		{
			$this->aUsers[$sAddress] = (object) array
			(
				"nickname" => $sNickname,
				"address" => $sAddress,
				"ownerAccount" => true,
				"style" => "",
			);
		}
		
		$this->parsePidginLog();
	}
	
	
	/**
	 *	Since Pidgin logs aren't exactly the best in the world, they
	 *	have to be, wait for it, parsed manually. Sad face. It's also
	 *	going to be very, very memory greedy.
	 */
	private function parsePidginLog()
	{
		$sString = $this->getLogString();
		$sPattern = '/Conversation with (.*?) at (.*?) on (.*?) \((.*?)\)/s';
		
		$bMatched = preg_match($sPattern, $sString, $aUserData);
		
		if(!$bMatched)
		{
			return false;
		}
		
		# Set up another user...
		$this->iSessionTime = strtotime($aUserData[2]);
		
		$this->aUsers[$aUserData[2]] = (object) array
		(
			"nickname" => "<unknown>",
			"address" => $aUserData[2],
			"ownerAccount" => false,
			"style" => "",
		);
		
		# Create the session
		$pSession = (object) array
		(
			"ownerAccount" => $aUserData[3],
			"sessionName" => $aUserData[1],
			"time" => $this->iSessionTime,
		);
		
		$this->sessionBegin($pSession);
		
		# Now, parse each line.
		$sPattern = '/<font (.*?)="(.*?)">(.*?)<br\/>/';
		
		preg_match_all($sPattern, $sString, $aMatches, PREG_SET_ORDER);
		
		foreach($aMatches as $aMessage)
		{
			switch($aMessage[1])
			{
				case "color":
				{
					$this->onMessage($aMessage);
					break;
				}
				case "size":
				{
					break;
				}
			}
		}
	}
	
	
	/**
	 *	Deal with parsing the message lines.
	 */
	private function onMessage($aMessage)
	{		
		$sComponentPattern = '/^<font size="2">\((.*)\)<\/font> <b>(.*):<\/b><\/font> (.*?)$/';
		$sStylePattern = "/<span style='(.*?)'>/";
		
		preg_match($sComponentPattern, $aMessage[3], $aMessageComponents);
		preg_match_all($sStylePattern, $aMessageComponents[3], $aMessageStyle);
		
		$iTimestamp = $this->generateMessageTS($aMessageComponents[1]);		
		$sMessageStyle = implode(" ", $aMessageStyle[1]);
		$pUser = $this->guessUserObject($aMessageComponents[1]);
		$sMessageString = strip_tags($aMessageComponents[3]);
		
		
	}
	
	
	/**
	 *	Generate the message timestring.
	 */
	private function generateMessageTS($sTimestamp)
	{
		$sDate = date("d-m-Y", $this->iSessionTime);
		$iComparisonStamp = strtotime("{$sDate} {$sTimestamp}");
		
		if($iComparisonStamp <= $this->iSessionTime)
		{
			$sDate = date("d-m-Y", $this->iSessionTime + 86400);
			return strtotime("{$sDate} {$sTimestamp}");
		}
		
		return $iComparisonStamp;
	}
	
	
	/**
	 *	Because Pidgin lacks the ability to err, do anything useful
	 *	regarding logs, one has to divide by zero, kill some frogs,
	 *	that kind of thing. What? It's an appropriate method name!
	 */
	private function guessUserObject($sNickname)
	{
		foreach($this->aUsers as $pUser)
		{
			if($pUser->nickname == $sNickname)
			{
				# Oh, my god! We've actually found him!
				return $pUser;
			}
		}
		
		# Okay, so now we've guessed that this is the other guy.
		# That's why it's named like that!
		
	}
}