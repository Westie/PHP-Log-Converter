<?php
/**
 *	The start up file for Log Converter.
 *
 *	Author:		David Weston <westie@typefish.co.uk>
 *
 *	Version:        <version>
 *	Git commit:     <commitHash>
 *	Committed at:   <commitTime>
 *
 *	Licence:	http://www.typefish.co.uk/licences/
 */


/**
 *	We need to include files. Great!
 */
include "System/Core/Core.php";
include "System/Core/Definitions.php";
include "System/Core/InputTemplate.php";
include "System/Core/OutputTemplate.php";
include "Definitions.php";


/**
 *	Incorporate templates.
 */
Core::incorporateTemplate("Adium");
Core::incorporateTemplate("MessengerPlus");
Core::incorporateTemplate("Purple");
Core::incorporateTemplate("stdout");


Core::convertLogs("2011-05-02.194443+0100IST.html", "Purple", "stdout");