<?php
class User_Bulk_Add extends SpecialPage
{
    var $mSubject,$mBody;

    function User_Bulk_Add()
    {
	SpecialPage::SpecialPage("User_Bulk_Add");
	#wfLoadExtensionMessages( 'User_Bulk_Add' ) ;
    }
    
    function execute( $par )
    {
	global $wgAuth, $wgUser, $wgRequest, $wgOut, $wgDBserver, $wgServer ;
	global $wgSitename, $wgScriptPath ;
	
	$this->setHeaders() ;

	$this->mSubject = wfMsg( 'user_bulk_add_subject', $wgSitename ) ;
	$from = $wgUser->getRealName() ;
	$this->mBody = wfMsg( 'user_bulk_add_body', $wgSitename,
	                      $wgServer .  $wgScriptPath, $from ) ;
	if (!$wgUser->isAllowed( 'cedar_admin' ) )
	{
		$wgOut->addHTML( "not allowed to upload files\n" ) ;
		return false ;
	}

	$action = $wgRequest->getText('action');
	if( $action == "add" )
	{
	    return $this->addUsers() ;
	}
	$this->addUsersForm() ;

	return true ;
    }

    private function addUsersForm()
    {
	global $wgAuth, $wgUser, $wgRequest, $wgOut, $wgDBserver, $wgServer ;
	global $wgScriptPath ;

	$wgOut->addHTML( "<FORM name=\"user_bulk_add\" action=\"$wgServer$wgScriptPath/index.php/Special:User_Bulk_Add\" method=\"POST\" enctype=\"multipart/form-data\">\n" ) ;

	$wgOut->addHTML( "<input type=\"hidden\" name=\"action\" value=\"add\" />\n" ) ;

	// MAX_FILE_SIZE must precede the file input field
	$wgOut->addHTML( "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"30000\" />\n" ) ;

	// Name of input element determines name in $_FILES array
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;\">User list file</SPAN>: <input type='file' name='user_file' id='user_file'/>\n" ) ;

	// text area with newline separated list of users to add where users
	// information is real name,email,username
	$wgOut->addHTML( "<br><br>\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;\">Enter users here</SPAN><br>\n" ) ;
	$wgOut->addHTML( "<textarea name=\"user_list\" rows=\"5\" cols=\"30\">Joe Smith,j.smith@jsmith.org,j.smith</TEXTAREA>\n" ) ;

	// group to add these new users to
	$wgOut->addHTML( "<br><br>\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;\">Group to add new users to</SPAN>: <input type=\"text\" NAME=\"group\" SIZE=\"40\">\n" ) ;
	$wgOut->addHTML( "<br><SPAN STYLE=\"font-size:8pt;\">blank if no group</SPAN>\n" ) ;

	// subject (displays the default subject if any)
	$wgOut->addHTML( "<br><br>\n" ) ;
	$subject = $this->mSubject ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;\">Subject of email to new users</SPAN>: <input type=\"text\" NAME=\"subject\" VALUE=\"$subject\" SIZE=\"40\">\n" ) ;

	// body of message (displays the default body). This is in addition
	// to the username, temporary password, link information.
	$wgOut->addHTML( "<br><br>\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;\">Body of email to new users</SPAN><br>\n" ) ;
	$body = $this->mBody ;
	$wgOut->addHTML( "<textarea name=\"body\" rows=\"10\" cols=\"30\">$body</TEXTAREA>\n" ) ;

	// submit and reset buttons
	$wgOut->addHTML( "<br><br>\n" ) ;
	$wgOut->addHTML( "<input type=\"submit\" value=\"submit\" />&nbsp;&nbsp;<input type=\"RESET\" value=\"reset\">\n" ) ;

	$wgOut->addHTML( "</FORM>\n" ) ;
    }

    private function addUsers()
    {
	global $wgUploadDirectory, $wgRequest, $wgOut, $wgDBserver, $wgServer ;

	$name = $wgRequest->getFileName( 'user_file' ) ;
	$subject = $wgRequest->getText( 'subject' ) ;
	$body = $wgRequest->getText( 'body' ) ;
	$group = $wgRequest->getText( 'group' ) ;
	if( $name && $name != "" )
	{
	    $name = $wgRequest->getFileName( "user_file" ) ;
	    $size = $wgRequest->getFileSize( "user_file" ) ;
	    $tmp_name = $wgRequest->getFileTempname( "user_file" ) ;
	    $error = $wgRequest->getUploadError( "user_file" ) ;
	    $uploaddir = $wgUploadDirectory ;
	    $uploadfile = $uploaddir . "/temp/" . basename( $name ) ;
	    if( !move_uploaded_file( $tmp_name, $uploadfile ) )
	    {
		$wgOut->addHTML( "Not able to upload file<br/>\n " ) ;
		$wgOut->addHTML( "name = $name<br/>\n" ) ;
		$wgOut->addHTML( "size = $size<br/>\n" ) ;
		$wgOut->addHTML( "tmp_name = $tmp_name<br/>\n" ) ;
		$wgOut->addHTML( "uploaddir = $uploaddir<br/>\n" ) ;
		$wgOut->addHTML( "uploadfile = $uploadfile<br/>\n" ) ;
		$wgOut->addHTML( "error = $error<br/>\n" ) ;
		return false ;
	    }

	    $ret = $this->addUsersFromFile( $uploadfile, $subject, $body, $group ) ;
	    unlink( $uploadfile ) ;
	    return $ret ;
	}
	else
	{
	    $list = $wgRequest->getText('user_list');
	    if( $list != "" && $list != "Joe Smith,j.smith@jsmith.org,j.smith" )
	    {
		return $this->addUsersFromList( $list, $subject, $body, $group ) ;
	    }
	    else
	    {
		$wgOut->addHTML( "<span style=\"color:red;\">MUST PROVIDE A FILE OR A LIST</span><br/><br/>\n" ) ;
		$this->addUsersForm() ;
	    }
	}

	return true ;
    }

    private function addUsersFromList( $user_list, $subject, $body, $group )
    {
	global $wgRequest, $wgOut, $wgServer ;

	$user_array = explode( "\n", $user_list ) ;
	$num_users = count( $user_array ) ;
	for( $i = 0; $i < $num_users; $i++ )
	{
	    $user_line = trim( $user_array[$i] ) ;
	    if( $user_line != "" )
	    {
		$user = explode( ",", $user_line ) ;
		$fields = count( $user ) ;
		if( $fields != 3 )
		{
		    $wgOut->addHTML( "malformed line, skipping $user_line<br>\n" ) ;
		}
		else
		{
		    $realname = trim( $user[0] ) ;
		    $email = trim( $user[1] ) ;
		    $username = trim( $user[2] ) ;
		    $this->addUser( $realname, $email, $username,
				    $subject, $body, $group ) ;
		}
	    }
	}

	return true ;
    }

    private function addUsersFromFile( $file_name, $subject, $body, $group )
    {
	global $wgAuth, $wgUser, $wgRequest, $wgOut, $wgDBserver, $wgServer ;

	$file = fopen( $file_name, "r" ) ;
	if( !$file )
	{
	    $wgOut->addHTML( "could not read user file\n" ) ;
	    return false ;
	}

	$num_added = 0 ;
	while( $fields = fgetcsv( $file ) )
	{
	    $realname = $fields[0] ;
	    $email = $fields[1] ;
	    $username = $fields[2] ;
	    $this->addUser( $realname, $email, $username, $subject, $body, $group ) ;
	}
    }

    private function addUser( $realname, $email, $username, $subject, $body, $group)
    {
	global $wgAuth, $wgUser, $wgRequest, $wgOut, $wgDBserver, $wgServer ;

	$wgOut->addHTML( "$realname - $email - $username - obj" ) ;

	$u = User::newFromName( $username, 'creatable' );
	if( is_null( $u ) )
	{
	    $wgOut->addHTML( " ... FAILED - could not create user object" ) ;
	    return false ;
	}
	if( 0 != $u->idForName() )
	{
	    $wgOut->addHTML( " ... FAILED - user already exists" ) ;
	    return false ;
	}
	if( !$wgAuth->addUser( $u, '' ) )
	{
	    $wgOut->addHTML( " ... FAILED - couldn't authorize new user" ) ;
	    return false ;
	}

	$wgOut->addHTML( "... stats" ) ;
	# Update user count
	$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
	$ssUpdate->doUpdate();

	$wgOut->addHTML( "... setting info" ) ;
	$u->addToDatabase();
	$u->setPassword( null );
	$u->setEmail( $email );
	$u->setRealName( $realname );
	$u->setToken();

	$wgAuth->initUser( $u );

	// Wipe the initial password and mail a temporary one
	$wgOut->addHTML( "... passwd" ) ;
	$u->setPassword( null );
	$np = $u->randomPassword();
	$u->setNewpassword( $np, false );
	$u->saveSettings();
	$id = $u->getId() ;

	// if a group was specified, then add the group information
	if( $group && $group != "" )
	{
	    $dbw =& wfGetDB( DB_MASTER );
	    $group = $dbw->strencode( $group ) ;
	    $wgOut->addHTML( "... group" ) ;
	    $dbw =& wfGetDB( DB_MASTER );
	    $dbw->insert( 'user_groups',
		    array(
			    'ug_user' => $id,
			    'ug_group' => $group,
		    ),
		    __METHOD__
	    ) ;
	}

	$wgOut->addHTML( "... emailing" ) ;
	$info = wfMsg( 'user_bulk_add_info', $realname, $username, $np ) ;
	$m = "$info$body" ;

	$result = $u->sendMail( $subject, $m );
	if( WikiError::isError( $result ) ) {
	    $wgOut->addHTML( "FAILED<BR />" ) ;
	    $wgOut->addWikiText( wfMsg( 'mailerror', $result->getMessage() ) ) ;
	    return false ;
	}

	// there might be some internal hooks
	$wgOut->addHTML( "... hooks" ) ;
	wfRunHooks( 'AddNewAccount', array( $u ) );

	$wgOut->addHTML( "... done<br>\n" ) ;
	$u = 0;

	return true ;
    }
}
?>

