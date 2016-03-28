This extension allows a sysops to bulk add users to the wiki.

To use this
extension you will need to add a few things to your LocalSettings.php file.
They are:

1. To activate the extension in the wiki you need to add
require_once( "$IP/extensions/user_bulk_add/user_bulk_add.php");

2. An email is sent to each user added to the wiki. The subject and the body
of the email includes a description of the wiki, for example:
$wgSitedescript     = "Cedar 2008 Workshop";

This variable will be used in the subject like so:
[Cedar 2008 Workshop] Wiki account created for you',

And the body of the email will look something like this:

John Smith

username: j.smith
temporary password: Qg57skx

An account has been created successfully for you with the above information
for Cedar 2008 Workshop http://cedarweb.hao.ucar.edu/wiki.

The first time you log in to the wiki with this temporary password you will
be asked to create a new password. Once you have created your new, permanent
password you will be able to make updates to the wiki.

Joe Admin

------------------------------
To use this extension will will be asked to either upload a file with the
new users, one user per line, or to enter the new users in a text box, one
user per line. The format of the lines is as follows:

John Smith,j.smith@domain.org,j.smith

<realname>,<email>,<username>

NOTE: No group assignments are made.

You can also change the subject and the body of the email sent to the new
users in the bulk add form.

