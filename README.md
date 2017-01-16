# PHP Member Check-in System

Originally created by Kevin Viratyosin and Jack O'Leary?

This is the check-in system, which is hosted on OpenShift.
This readme is a brief overview of the code for the check-in system


## Code Structure

* `index.php`:
    Imports the files we use, contains the shell of the interface (determines when to prompt for login, sets up some of 
    the basic JS handling for the interface, and chooses what page to show then calls the relevant JS file)
* `auth.php`:
    Handler functions for the user login system
* `data.php`:
    Where most of the server-side code lives. 
    Functions for dues, functions for check in etc., and the handling logic for post requests)

* `resources`:
    * `config.php`:
        Things I consider to be "configurations" go here. 
        Contains constants to setup the environment for the current semester, payment amounts, etc.
    * `modals/`:
        Contains the templates for the modals 
    * `js/`:
        JS files, for client-side handling
    * `containers/`:
        Templates for displaying data etc. on the site
    
* `css/main.css`:
    The CSS for the site
    

If you're looking for ...

* the *layout of the modals* -> resources/modals/
* the *callback* functions on the *modals* -> Unfortunately this isn't all in one place. Callbacks are *mostly* setup in 
member.js. Some modals require more setup, which is generally in index.php. If that fails, try searching the whole 
project for the ids on buttons.
* the *layout of the interface* (besides the modals) -> index.php is the top-level control of what gets shown; the files
in resources/containers/ are the templates for things like the member view interface, search interface, and summary.
* *how that interface is controlled or changes* -> Check index.php and the js/ directory/. Specifically, the user login 
for volunteers is in js/auth.js and resources/modals/loginModal.php. Otherwise, js/member.js is kind of a jumble.
* something related to *login* -> check js/auth.js, resources/modals/loginModal.php and auth.php
* the *backend* or *most things that touch the database* -> Check data.php, which routes commands to the proper 
functions. The actual functions are in resources/*.php.


## Setting up for a new semester

If you are a new web-coordinator, and haven't used the check-in system yet, you should first create a new user for yourself with the "Web" role. 
To do this, log into the system as admin, and there should be a "New User" button in the nav bar which you can use to add yourself.

Before each semester, you currently have to manually input a bunch of dates and stuff, mostly in `config.php`. 

* `$CURRENT_TERM`, `$CURRENT_START_DATE`, `$CURRENT_END_DATE`, `$COMP_DUE_DATE_TABLE`, and `$COMP_PRACTICES_TABLE` at least
* If the payment method etc. has changed since the last semester, you may need to change more code
  
