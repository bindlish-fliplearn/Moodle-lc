**********************************************
How to install WizIQ plugin in Moodle
**********************************************

1. Create an account on www.wiziq.com
2. Take Moodle plan on www.wiziq.com/moodle
3. Click on download Moodle plugin and you will be redirected to “http://www.wiziq.com/moodle/Thanks.aspx” page.
4. Select Moodle Version and click on “Start download” button to download plugin.
5. On clicking “start download” button, you will be redirected to “http://www.wiziq.com/moodle/download-plugin.aspx”  page and plugin will be download in zip format.
6. Unzip the WizIQ plugin and copy “WizIQ” folder and paste that folder in your Moodle set up at location “/mod/” .
7. Now login to your Moodle account as “Admin” .
8. Click on “Notification” and you will be redirected to “Plugins Check” page.
9. Click on “Upgrade” button on “Plugins Check” page.
10.Notice that “mod_wiziq” displaying on screen with “Success” message.
11.Now click on “Continue” button and you will be redirected to “/admin/upgradesettings.php” settings page of WizIQ plugin.
12.Check your settings and click on “save settings” button and you will be redirected to your Moodle.

OR

1. Create an account on www.wiziq.com
2. Take Moodle plan on www.wiziq.com/moodle
3. Click on download Moodle plugin and you will be redirected to “http://www.wiziq.com/moodle/Thanks.aspx” page.
4. Select Moodle Version and click on “Start download” button to download plugin.
5. On clicking “start download” button, you will be redirected to “http://www.wiziq.com/moodle/download-plugin.aspx”  page and plugin will be download in zip format.
6. Loginto your Moodle setup as admin
7. Go to Plugin under site administration
8. choose Install Plugin 
9. Choose the downloaded zip file from your systeam
10.Install the plugin

PS if you are old WizIQ plugin user it is advised to take backup of all the data before installation.

**********************************************
Wiziq Capabilities for Moodle 3.3
**********************************************
1. Notification / reminders emails from Plugin.
2. All class [Single / Recurring ] options inside a single plugin.
3. View / download Recording stats
4. Content Upload : At Course level.
2. Download recording : At module level. 
3. View recording : At module level.
4. View Attendance report : At module level.

Note:-
libxml_use_internal_errors(true); // This function is used to hide the warning if the xml is not parsed, we have used this function in the try catch block so if any exception occurs it will be caught in the catch block. The exception in try block regarding the xml will only come if no xml or ill-framed xml is recieved form wiziq.

**********************************************
Enhancement in WizIQ plugin for Moodle
**********************************************

1. Performance is improved in wiziq "index.php" page, class details for all classes are fetched in groups from api for better execution of page.
2. Standard and Legacy Logs,both are added for all the activities related to WizIQ for keeping record of various activities in WizIQ for eg: 
   a. View class get data
   b. View class index getdata
   c. Add attendee
   d. Delete class
   e. Modify class
   f. View download recording
   g. View attendance report
   h. Add create folder
   i. Add upload cotent
   j. Delete content
   k. Delete folder
   l. View getdata by session
   m. Viewall class by session
   n. Delete class by session
   o. Update content id

**********************************************
Following functions need to be active on server
**********************************************
1. Mcrypt function should be active on server.
2. cURL fucntion should be active on server.
3. simpleXML function should be active on server.
4. allow_url_fopen should be enabled.
5. allow_url_inclue should be enabled



