<?php
/**
 *	Messenger Plus input template for Log Converter.
 *
 *	Author:		David Weston <westie@typefish.co.uk>
 *
 *	Version:        <version>
 *	Git commit:     <commitHash>
 *	Committed at:   <commitTime>
 *
 *	Licence:	http://www.typefish.co.uk/licences/
 */


class MessengerPlusInput extends InputTemplate
{
	private
		$pXML = null,
		$aTime = null,
		$aUsers = null,
		$iMessageTime = null,
		$iSessionTime = null,
		$iNextDay = null;
	
	
	/**
	 *	Called when the Template is loaded.
	 */
	public function onConstruct()
	{
		try
		{
			$this->pXML = simplexml_load_file($this->LogName);
		
			$this->setServiceName("MSN");
		
			foreach($this->pXML->body->div as $pElement)
			{
				$this->parseSession($pElement);
			}
		}
		catch(Exception $e)
		{
			Core::println($e->getMessage());
		}
	}
	
	
	/**
	 *	Parse a message session.
	 */
	private function parseSession($pElement)
	{
		$this->aUsers = array();
		$this->aTime = array();
		$this->iMessageTime = null;
		$this->iSessionTime = null;
		$this->iNextDay = null;
		
		$this->generateSessionTS((string) $pElement['id']);
		
		$sFirstUser = "";
		$sSecondUser = "";
		
		$iCount = 0;
		
		# Parse the header
		foreach($pElement->ul->li as $pUser)
		{
			$sNickname = $this->getNickname(trim((string) $pUser));
			$sAddress = (string) substr($pUser->span, 1, -1);
			
			if(++$iCount == 1)
			{
				$sFirstUser = $sAddress;
			}
			if($iCount == 2)
			{
				$sSecondUser = $sAddress;
			}
			
			$this->aUsers[substr($sNickname, 0, 16)] = (object) array
			(
				"nickname" => $sNickname,
				"address" => $sAddress,
				"ownerAccount" => isset($pUser['class']),
				"style" => "",
			);
		}
		
		# Create the session
		$pSession = (object) array
		(
			"ownerAccount" => $sFirstUser,
			"sessionName" => $sSecondUser,
			"time" => $this->iSessionTime,
		);
		
		$this->sessionBegin($pSession);
		
		# Parse the messages
		foreach($pElement->table->tbody->tr as $pTable)
		{
			$sStatus = (string) $pTable['class'];
		
			$this->generateMessageTS(substr((string) $pTable->th->span, 1, -1));
			
			if($sStatus == "msgplus")
			{
				$this->onStatusChange($pTable);
			}
			elseif($sStatus == "messenger")
			{
				$this->onMessengerNotification($pTable);
			}
			else
			{
				$this->onMessage($pTable);
			}
		}
		
		$this->sessionEnd();
	}
	
	
	/**
	 *	Parse the status changes.
	 */
	private function onStatusChange($pTable)
	{
		$sString = html_entity_decode((string) $pTable->td);
		
		# I need to optimise this...
		if(preg_match("/^(.*?)[\s]is now[\s](.*)$/", $sString, $aMatch))
		{
			$pUser = $this->aUsers[substr($aMatch[1], 0, 16)];
			
			$pStatus = (object) array
			(
				"string" => strtolower($aMatch[2]),
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $this->iMessageTime,
				"style" => $pUser->style,
			);
			
			$this->userChangedStatus($pStatus);
			return;
		}	
		elseif(preg_match("/^(.*?)[\s]has changed his\/her personal message to \"(.*)\"$/", $sString, $aMatch))
		{
			$pUser = $this->aUsers[substr($aMatch[1], 0, 16)];
			
			$pMessage = (object) array
			(
				"string" => $aMatch[2],
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $this->iMessageTime,
				"style" => $pUser->style,
			);
			
			$this->userChangedStatusMessage($pMessage);
			return;
		}
		elseif(preg_match("/^(.*?)[\s]has changed his\/her status to \"(.*)\"$/", $sString, $aMatch))
		{
			$pUser = $this->aUsers[substr($aMatch[1], 0, 16)];
			
			$pStatus = (object) array
			(
				"string" => strtolower($aMatch[2]),
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $this->iMessageTime,
				"style" => $pUser->style,
			);
			
			$this->userChangedStatus($pStatus);
			return;
		}
		elseif(preg_match("/^(.*?)[\s]has changed his\/her name to \"(.*)\"$/", $sString, $aMatch))
		{
			$sNickname = substr($aMatch[1], 0, 16);
			$pUser = $this->aUsers[$sNickname];
			
			$pNick = (object) array
			(
				"string" => $aMatch[2],
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $this->iMessageTime,
				"style" => $pUser->style,
			);
			
			$this->userChangedNickname($pNick);
			
			$this->aUser[substr($aMatch[2], 0, 16)] = $this->aUsers[$sNickname];
			unset($this->aUsers[$sNickname]);
			
			return;
		}
	}
	
	
	/**
	 *	Parses all Messenger events.
	 */
	private function onMessengerNotification($pTable)
	{
		# Hurrah for message cleaning
		$sString = html_entity_decode($pTable->td->asXML());
		$sString = $this->removeImages($sString);
		
		if(preg_match("/^<td>(.*?)[\s]just sent you a nudge\.<\/td>$/", $sString, $aMatch))
		{
			$pUser = $this->aUsers[$this->getNickname($aMatch[1], true)];
			
			$pAlert = (object) array
			(
				"string" => "just sent you a nudge",
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $this->iMessageTime,
				"style" => $pUser->style,
			);
			
			$this->userSentAlert($pAlert);
			
			return;
		}
	}
	
	
	/**
	 *	Parses the messages.
	 */
	private function onMessage($pTable)
	{
		# Messy definitions
		$sStyle = (string) html_entity_decode($pTable->td['style']);
		
		$sNickname = substr((string) $pTable->th->asXML(), 38, -6);
		$sNickname = $this->getNickname($sNickname, true);
		
		$pUser = $this->aUsers[$sNickname];
		
		$pUser->style = $sStyle;
		
		# Hurrah for message cleaning
		$sMessage = html_entity_decode($pTable->td->asXML());
		$sMessage = $this->removeImages(substr($sMessage, (13 + strlen($sStyle)), -5));
					
		$aMessageLines = explode("<br />", $sMessage);
		
		foreach($aMessageLines as $sMessageLine)
		{
			$pMessageObject = (object) array
			(
				"string" => $sMessageLine,
				"nickname" => $pUser->nickname,
				"address" => $pUser->address,
				"time" => $this->iMessageTime,
				"style" => $pUser->style,
				"action" => false,
			);
			
			$this->userSentMessage($pMessageObject);
		}
	}
	
	
	/** 
	 *	Generates a session time stamp. Used to calculate when the conversation began.
	 *	I'm having to make the session time 5 minutes before, to counteract the possibility
	 *	of a bug.
	 */
	private function generateSessionTS($sSessionID)
	{
		preg_match('/Session_(.*)-(.*)-(.*)T(.*)-(.*)-(.*)/', $sSessionID, $this->aTime);

		# Grr... why did you have to make the session time in the future in
		# comparison to the first post?
		$this->aTime[5] -= 5;
		
		$this->iSessionTime = mktime($this->aTime[4], $this->aTime[5], $this->aTime[6], $this->aTime[2], $this->aTime[3], $this->aTime[1]);
		$this->iNextDay = mktime($this->aTime[4], $this->aTime[5], $this->aTime[6], $this->aTime[2], ($this->aTime[3] + 1), $this->aTime[1]);
	}
	
	
	/**
	 *	Generates a message time stamp.
	 *	It takes seconds, too!
	 */
	private function generateMessageTS($sTimeString)
	{
		$aTime = explode(':', $sTimeString, 3);
		
		if(!isset($aTime[2]))
		{
			$aTime[2] = $this->aTime[6] + 1;
		}
		
		$iMessageTime = ($aTime[0] * 3600) + ($aTime[1] * 60) + $aTime[2];
		$iConversationStart = ($this->aTime[4] * 3600) + ($this->aTime[5] * 60) + $this->aTime[6];
		
		$iDifference = $iMessageTime - $iConversationStart;
		
		if($iDifference < 0)
		{			
			$this->iMessageTime = $this->iNextDay - $iDifference;
		}
		else
		{
			$this->iMessageTime = $this->iSessionTime + $iDifference;
		}
	}
	
	
	/**
	 *	Returns the alternative text from an image - namely emoticons.
	 */
	private function removeImages($sText)
	{
		return preg_replace('/<img src="(.*?)" alt="(.*?)" \/>/', '$2', $sText);
	}
	
	
	/**
	 *	Return nickname substring
	 */
	private function getNickname($sNickname, $iSub = false)
	{
		$sNickname = html_entity_decode($sNickname);
		$sNickname = $this->removeImages($sNickname);
		
		$sNickname = str_replace(chr(160), chr(32), $sNickname);
		
		if($iSub)
		{
			$sNickname = strip_tags($sNickname);
			return substr($sNickname, 0, 16);
		}
		
		return $sNickname;
	}
}
