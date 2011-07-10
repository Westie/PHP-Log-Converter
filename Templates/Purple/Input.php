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
			"nickname" => "(unknown)",
			"address" => $aUserData[1],
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
		$sPattern = '/^<font (.*?)="(.*?)">(.*)(<br\/>|\n<\/b><br\/>)/m';
		
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
					$this->onEvent($aMessage);
					break;
				}
			}
		}
		
		$this->sessionEnd();
	}
	
	
	/**
	 *	Deal with parsing the message lines.
	 */
	private function onMessage($aMessage)
	{		
		$sComponentPattern = '/^<font size="2">\((.*)\)<\/font> <b>(.*):<\/b><\/font> (.*?)$/';
		$sStylePattern = "/<span style='(.*?)'>/";
		$bAction = false;
		
		preg_match($sComponentPattern, $aMessage[3], $aMessageComponents);
		preg_match_all($sStylePattern, $aMessageComponents[3], $aMessageStyle);
		
		# Is the user sending an action?
		if(substr($aMessageComponents[2], 0, 3) == "***")
		{
			$bAction = true;
			$aMessageComponents[2] = substr($aMessageComponents[2], 3);
		}
		
		# Sort the data out.
		$pUser = $this->guessUserObject($aMessageComponents[2]);			
		$pUser->style = implode(" ", $aMessageStyle[1]);
		
		$iTimestamp = $this->generateMessageTS($aMessageComponents[1]);	
		$aMessageLines = explode("<br/>", $aMessageComponents[3]);
		
		# Cycle through the thankfully combo-lined chats.
		foreach($aMessageLines as $sMessageLine)
		{
			$pMessageObject = (object) array
			(
				"string" => strip_tags($sMessageLine),
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $iTimestamp,
				"style" => $pUser->style,
				"action" => $bAction,
			);

			$this->userSentMessage($pMessageObject);
		}
	}
	
	
	/**
	 *	Deal with parsing the events - well, anything not a message.
	 */
	private function onEvent($aMessage)
	{
		print_r($aMessage);
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
	 *	I could use message colour, but that might become out of
	 *	date...
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
		foreach($this->aUsers as $pUser)
		{
			if($pUser->nickname == "(unknown)")
			{
				$pUser->nickname = $sNickname;
				return $pUser;
			}
		}
		
		return null;
	}
}