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


class AdiumOutput extends OutputTemplate
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
		$this->pXML = new SimpleXMLElement('<chat></chat>');
		$this->pSession = $pObject;
		
		$this->pXML->addAttribute("xmlns", "http://purl.org/net/ulf/ns/0.4-02");
		$this->pXML->addAttribute("account", $pObject->ownerAccount);
		$this->pXML->addAttribute("service", $this->getServiceName());
	}
	
	
	/**
	 *	Called when the session ends.
	 */
	public function sessionEnd()
	{
		$sResourceDirectory = $this->getTemplateOutputDirectory();
		
		$sLogDirectory = "{$sResourceDirectory}/".$this->getServiceName().".{$this->pSession->ownerAccount}/{$this->pSession->sessionName}/";
		$sLogDirectory .= "{$this->pSession->sessionName} (".date('c', $this->pSession->time).").chatlog";
		
		$sFileDirectory = "{$sLogDirectory}/{$this->pSession->sessionName} (".date('c', $this->pSession->time).").xml";
		
		mkdir($sLogDirectory, 0777, true);
		
		file_put_contents($sFileDirectory, $this->pXML->asXML());
		
		Core::println("Successfully converted {$this->pSession->sessionName} (".date('c', $this->pSession->time).")");
	}
	
	
	/**
	 *	Called when a user's status changes. It may be to whatever,
	 *	offline, online, etc.
	 */
	public function userChangedStatus($pObject)
	{
		$pElement = $this->pXML->addChild("status");
		
		$pElement->addAttribute("type", $pObject->string);
		$pElement->addAttribute("sender", $pObject->address);
		$pElement->addAttribute("time", date('c', $pObject->time));
		$pElement->addAttribute("alias", $pObject->nickname);
	}
	
	
	/**
	 *	Called when a user changes their status, or rather, personal message.
	 */
	public function userChangedStatusMessage($pObject)
	{
		$pElement = $this->pXML->addChild("status");
		
		$pElement->addAttribute("type", "online");
		$pElement->addAttribute("sender", $pObject->address);
		$pElement->addAttribute("time", date('c', $pObject->time));
		$pElement->addAttribute("alias", $pObject->nickname);
		
		$pContainer = $pElement->addChild('div');
		
		$pContents = $pContainer->addChild('span', $pObject->string);
		$pContents->addAttribute('style', str_replace('"', '\'', $pObject->style));
	}
	
	
	/**
	 *	Called when a user changes their nickname.
	 */
	public function userChangedNickname($pObject)
	{	
		$pElement = $this->pXML->addChild("event");
		
		$pElement->addAttribute("type", "libpurpleMessage");
		$pElement->addAttribute("sender", $pObject->address);
		$pElement->addAttribute("time", date('c', $pObject->time));
		$pElement->addAttribute("alias", $pObject->nickname);
		
		$pFrame = $pElement->addChild("div");
		
		$pContainer = $pFrame->addChild("a");
		$pContainer->addAttribute("href", "mailto:{$pObject->address}");
		
		$pAddress = $pContainer->addChild("span", $pObject->address);
		$pAddress->addAttribute("style", $pObject->style);
		
		$pContent = $pFrame->addChild("span", " is now known as {$pObject->nickname}.");
		$pContent->addAttribute("style", $pObject->style);
	}
	
	
	/**
	 *	Called when a user sends an alert.
	 */
	public function userSentAlert($pObject)
	{
		$pElement = $this->pXML->addChild("event");
		
		$pElement->addAttribute("type", "Notification");
		$pElement->addAttribute("sender", $pObject->address);
		$pElement->addAttribute("time", date('c', $pObject->time));
		$pElement->addAttribute("alias", $pObject->nickname);
		
		$pContents = $pElement->addChild("div", "{$pObject->nickname} wants your attention!");
	}
	
	
	/**
	 *	Called when a user sends a message.
	 */
	public function userSentMessage($pObject)
	{
		$pElement = $this->pXML->addChild("message");
		
		$pElement->addAttribute("sender", $pObject->address);
		$pElement->addAttribute("time", date('c', $pObject->time));
		$pElement->addAttribute("alias", $pObject->nickname);
		
		$pContainer = $pElement->addChild('div');
		
		$pContents = $pContainer->addChild('span', $pObject->string);
		$pContents->addAttribute('style', str_replace('"', '\'', $pObject->style));
	}
}