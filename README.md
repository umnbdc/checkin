# PHP Member Check-in System

Originally created by Kevin Viratyosin and Jack O'Leary?

This is the check-in system, which is hosted on OpenShift.
This readme is a brief overview of the code for the check-in system


## Code Structure

* `index.php`:
    Imports the files we use, contains the shell of the interface, and sets up some of the basic JS handling for the interface
* `auth.php`:
    Handler functions for the user login system
* `data.php`:
    Where the routing for the server POST requests lives. 

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
    * Various `____.php` files dividing the logic for POST requests
    
* `css/main.css`:
    The CSS for the site


## Setting up for a new semester

If you are a new web-coordinator, and haven't used the check-in system yet, you should first create a new user for yourself with the "Web" role. 
To do this, log into the system as admin, and there should be a "New User" button in the nav bar which you can use to add yourself.

Before each semester, you currently have to manually input a bunch of dates and stuff, mostly in `resources/config.php`. 

* `$CURRENT_TERM`, `$CURRENT_START_DATE`, `$CURRENT_END_DATE`, `$COMP_DUE_DATE_TABLE`, and `$COMP_PRACTICES_TABLE` at least
* If the payment method etc. has changed since the last semester, you may need to change more code
  
