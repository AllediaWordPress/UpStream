=== WordPress Project Management by UpStream ===
Contributors: upstreamplugin, deenison, andergmartins
Tags: project, manage, management, project management, project manager, wordpress project management, crm, client, client manager, tasks, issue tracker, bug tracker, task manager
Requires at least: 4.5
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: 1.19.0
License: GPL-3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

UpStream is a free but very powerful project management plugin for WordPress.

== Description ==

UpStream is a free project management plugin.

UpStream allows you to manage any type of project from inside your WordPress site.

Your clients can track the progress of their project via the frontend project view. Your team can see all the tasks and bugs that are assigned to them.

***[Click here to try a free demo of UpStream](https://upstreamplugin.com/demo)***

The UpStream core is totally free. We encourage you to try the demo and see how it works. UpStream also has a range of extensions that allow you to extend the features available for your projects.

View our [Premium Extensions](https://upstreamplugin.com/extensions/) here.

= Project Features =

* Milestones & Tasks (that can be linked)
* Bug/Issue Tracker
* Upload Files & Documents
* Project Discussion thread
* Automatic Progress Tracking
* Custom Fields
* Custom Statuses

= Client Features =

* Client contact details, address, logo
* Custom fields
* Client Users (employees)
* Client login page to view their projects

= General Features =

* Built in Roles - Project Manager & Project User
* Custom Capabilities & Permissions
* Awesome looking frontend
* Customizable frontend templates
* Label Projects, Clients, Milestones, Tasks, Files & Bugs anything you like
* Developer friendly and highly customizable
* Translation ready

= Premium Extensions =
Add even more awesome features through the use of our extensions.

- [Frontend Edit](https://upstreamplugin.com/extensions/frontend-edit)
- [Project Timeline](https://upstreamplugin.com/extensions/project-timeline)
- [Customizer](https://upstreamplugin.com/extensions/customizer)
- [Email Notifications](https://upstreamplugin.com/extensions/email-notifications)
- [Copy Project](https://upstreamplugin.com/extensions/copy-project)
- [Calendar View](https://upstreamplugin.com/extensions/calendar-view)
- [Custom Fields](https://upstreamplugin.com/extensions/custom-fields)


= Milestones & Tasks =

Milestones & tasks help you to successfully plan, track and manage your project from start to finish. Assign tasks & milestones to users, add start & end dates, color-coded statuses, notes and progress of the tasks & milestones. You can even add your own custom fields.

= Bug Tracking & Issue Reporting =

Easily report bugs or issues as they arise and just like milestones & tasks, you can assign the bug to a user, add a status, severity of the bug, a description, due date & attach files to each individual bug.

= Project Discussion =

Avoid email trails and keep the entire discussion about your project right where it should be, within the project! Any user can add to the discussion and with the Front End Edit extension and you can also allow your clients to add to the discussion.

= Front End View =

Your clients can view the details and the progress of the project via the front end. Clients can never access the WordPress admin. Using a customized login system, you can determine which users of your client can have access to the project and also to which parts of the project they can view.

= Highly Customizable =

Well thought out settings and options, customizable templates, add your own CSS, create custom fields wherever you like, create your own statuses with whatever colors you choose plus lots more. You can even rename projects, milestones, tasks, bugs, files and clients. Prefer to rename ‘Bugs’ as ‘Issues’? Rather call a ‘Project’ a ‘Plan’ or call a ‘Client’ a ‘Customer’? Go for it!

== Installation ==

= Minimum Requirements =

* WordPress 4.5 or greater
* PHP version 5.6 or greater

= Setting Up =

1. Activate the plugin
2. Go to UpStream > General Settings and configure the options as required
3. Create a Client by going to Projects > New Client
4. Create a Project by going to Projects > New Project
5. For a Quick Start guide and more detailed instructions, please visit the [Documentation](https://upstreamplugin.com/documentation/) page.


== Frequently Asked Questions ==

= Where can I find UpStream documentation? =

For a Quick Start guide and more detailed instructions, please visit the official [Documentation](https://upstreamplugin.com/documentation/) page

= Where can I get support? =

You can ask for help in the [UpStream Plugin Forum](https://wordpress.org/support/plugin/upstream).

= Will UpStream work with my theme? =

Yes, UpStream works independent of any theme.

= Why doesn't the UpStream frontend look like my theme? =

UpStream does not use the existing styling of your theme. The features and the very specific nature of the plugin make it impossible to integrate into existing themes. The plugin is highly customizable though, so you can tweak it to look the way you want it to.


== Screenshots ==

1. Editing a Project
2. Frontend view
3. List of Tasks
4. Editing a Milestone
5. Project settings
6. All project activity is logged
7. Adding a Bug
8. Editing a Client
9. Close up of Project Timeline (premium extension)


== Upgrade Notice ==

= 1.16.2 =
If you were having date issues within Projects, please clear your cache and re-save any item that was being affected by it.


== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com)
and this project adheres to [Semantic Versioning](http://semver.org).

= [1.19.0] - 2018-07-04 =

Changed:
* Improved the style in the extensions page;
* Highlighter upgrade and extensions links;
* Added form and message in the settings page to subscribe and earn 20% off and ask for review;

= [1.18.4] - 2018-06-22 =

Fixed:
* Fixed style for the admin bar which was being displayed in the bottom-left;

= [1.18.3] - 2018-06-20 =

Fixed:
* Fixed the admin bar, removing code that was hiding it. It is displayed in the front-end now;
* Fixed the discussion menu item in the sidebar when discussion is disabled;

Changed:
* Updated cmb2 library;

= [1.18.2] - 2018-06-07 =

Fixed:
* Fixed a PHP warning when there is no client users in the project;
* Fixed a PHP warning about wrong data type on the second param in the in_array function;
* Fixed the height of navigation buttons buttons of the calendar;
* Fixed the count of tasks and bugs in the project in the admin and front-end;
* Fixed the form validation error highlighting displaying more relevant and visible error message;
* Fixed hardcoded labels for "Discussion" and added field to customize the string;
* Fixed hardcoded labels for "Client";
* Fixed the milestone titles in the activity box;

Changed:
* Removed the list of projects from the sidebar;
* Removed the vertical line from the menu in the sidebar;
* Added new icon for the project in the sidebar;
* Declared the method setContentHeight in the global scope in JS for add-ons;
* Removed arrow icon from menus in the sidebar;

Added:
* Added new hooks to display specific views in the front-end, for add-ons;

= [1.18.1] - 2018-05-29 =

Changed:

* Rename templates/assets/lib folder to templates/assets/libraries;

= [1.18.0] - 2018-05-29 =

Fixed:
* Fixed the rewrite rules after activating/deactivating the plugin, flushing the rules to refresh;
* Fixed the method to format date using the correct timezone;
* Fixed a typo in the footer;

Added:
* Added option to change the projects and client URL in the front-end;
* Added new JS library for select fields, chosen - available for add-ons;
* Added method to convert date to unixtime: upstream_date_unixtime;
* Added method to return an array with a list of client's users: upstream_get_all_client_users
* Added new actions;

Changed:
* Updated the .pot file;
* Removed little chain icon from the list of projects;

= [1.17.0] - 2018-04-26 =

Added:
* Added action "upstream:frontend.project.details.after_title"

Changed:
* Increased spacing between filters section and data rows within Projects in wp-admin

Fixed:
* Fixed major architecture flaw where Projects were losing track of Project Statuses, Milestones Statuses, Tasks Statuses, Bugs Statuses/Severities if they were changed through UpStream settings
* Fixed some Projects description not being rendered as HTML
* Small text update on the "Project Progress Icons" options
* Fixed Notes/Description losing their formatting on frontend
* Fixed bug where it was impossible to expand table rows on frontend browsing through small-screens

= [1.16.4] - 2018-04-18 =

Changed:
* Increased maximum execution time for frontend scripts
* Minor performance enhancements on front end pages

Fixed:
* Fixed uncommon bug where jQuery UI DatePicker plugin was being loaded on frontend
* Fixed filters on admin project page that can have multiple values
* Fixed permissions check failing for items having multiple assignees
* Fixed PHP warnings

= [1.16.3] - 2018-04-02 =

Fixed:
* Fixed comments not being displayed anymore

= [1.16.2] - 2018-03-27 =

Added:
* Added option under user's profile to choose whether to be notified when someone replies to his comments

Changed:
* Users are now notified about comment replies

Fixed:
* Fixed yet another error with malconversion of some time zones

= [1.16.1] - 2018-03-13 =

Changed:
* Changed "Disable Project Overview" option label to "Project Progress Icons"

Removed:
* Removed deprecated methods on v1.15.0

Fixed:
* Fixed avatar infinite multiplication after adding new items to a Project in wp-admin
* Fixed recent PHP warnings thrown under PHP 7.2

= [1.16.0] - 2018-03-08 =

Added:
* Users can be assigned to Files
* Client Users can also be assigned to Milestones/Tasks/Bugs/Files

Changed:
* Managers can now assign multiple users to Milestones, Tasks and Bugs
* Minor text changes on Start/End Date filters

Fixed:
* Fixed error message shown on frontend after changing Severity/Status/Milestone names
* Fixed errors while adding/changing Client logo in admin

= [1.15.1] - 2018-02-22 =

Changed:
* "Title" search fields placeholders are not individually i18n scoped anymore

Fixed:
* Fixed 404 redirects after login/logout in some environments
* Fixed Start/End/Due Date fields not always being stored as GMT/UTC

= [1.15.0] - 2018-02-15 =

Added:
* Added Categories, Status, Clients, Title filters for Projects on frontend
* Added Milestone, Assignee, Star and End Dates filters for Milestones
* Added Title, Assignee, Status, Milestone, Star and End Dates filters for Tasks
* Added Title, Assignee, Severity, Status, Due Date filters for Bugs
* Added Title, Uploader, Upload Date filters for Files
* Added "Owner" and "Client" filter to the admin Projects list
* Project Owners will receive comment notifications
* Assigned users and creators now receive notifications about comments on their item

Changed:
* Users can now filter metaboxes/tables data using multiple filters at once
* We're slowly moving towards using Select2 lib across the whole plugin
* Frontend Date filters now use a new Date Picker js lib
* Replaced wp_verify_nonce in favor of check_ajax_referer on the comments AJAX endpoints
* Minor text changes
* Update year in copyright info

Deprecated:
* Within UpStream_Metaboxes_Projects class: getStatusFilterHtml, getSeverityFilterHtml, getFiltersHeaderHtml, getFiltersFooterHtml, getMilestoneFilterHtml

Removed:
* Frontend tables no longer use Datatable lib due lack of flexibility and performance issues

Fixed:
* Fix Comments label missing from Screen Options pulldown in the Projects page
* Fixed Status filter in Projects admin list getting reseted after being selected
* Fixed Project author not receiving comment notifications
* Fixed Start/End Dates intervals

= [1.14.1] - 2018-02-12 =

Fixed:
* Fixed CMB2 not being loaded correctly in a multisite environment
* Fixed some DB calls triggering errors in multisite environments

= [1.14.0] - 2018-01-31 =

Added:
* Tags can now be assigned to Projects
* Added "Disable Project Overview" option
* Added "Disable Project Details" option
* Auto scroll to particular comments via URL

Changed:
* "Comments on <section>" options labels were renamed to "Disable Discussion on <section>"
* Some options were grouped for better UX

Fixed:
* Fixed a couple of strings not being translated as they should

= [1.13.7] - 2018-01-26 =

Removed:
* Remove notice about recent changes made on Clients
* Remove deprecated code

Fixed:
* Fixed some Comments tabs not working on admin
* Fixed some potential PHP errors and warnings

= [1.13.6] - 2018-01-15 =

Changed:
* Users can no longer be added via Clients page

Deprecated:
* Legacy Users migration class/functions/methods were marked as deprecated

Removed:
* Removed upstream_disable_discussions() deprecated function

Fixed:
* Fixed conflict with Sliced Invoices plugin
* Fixed bug where items comments were not being retrieved on admin
* Fixed some dates being converted when they shouldn't
* Fixed long user names overflowing on frontend sidebar
* Fixed Notes/Description fields losing their format on frontend
* Fixed not being able to assign existent users to Clients

= [1.13.5] - 2018-01-04 =

Changed:
* Changed no data message for consistency across sections on frontend
* Update CMB2 to v2.3.0
* Legacy Client Users Migration script and related methods were marked as deprecated and will be removed on future releases

Fixed:
* Fixed some bad redirects relying on home_url() instead of site_url()
* Removed stray "none" text from Discussion section in admin
* Fixed some users not being able to save/update Projects on admin
* Fixed some PHP warnings thrown while adding comments

= [1.13.4] - 2017-12-29 =

Fixed:
* Fixed sidebar icon on admin in some pages

= [1.13.3] - 2017-12-29 =

Fixed:
* Fixed white screen on settings page
* Fixed CMB2 loading bug

= [1.13.2] - 2017-12-29 =

Added:
* Added new extension: Custom Fields
* Added new filter that allow custom post types to load CMB2 in admin

Changed:
* Display none to empty Notes/Description/Comments fields
* Removed "Settings" label from settings sub menu items
* CMB2 lib was updated to v2.2.6.2
* Lang files cleanup
* Project Comments section was renamed back to Discussion

Fixed:
* Fixed some assets being loaded on every page
* Fixed missing Discussion link on the frontend sidebar

= [1.13.1] - 2017-12-07 =

Fixed:
* Fix Bugs widget on frontend using Tasks statuses labels instead
* Fixed wrong redirects for some non UpStream users
* Fixed some users not being able to access their own posts
* Fixed post listing being empty for some users outside UpStream's scope
* Fixed potential PHP error on frontend
* Fixed Description/Notes losing line breaks on frontend display

= [1.13.0] - 2017-11-30 =

Added:
* Added support for comment replies
* Added Discussion/Comments to Milestones, Tasks, Bugs, Files

Changed:
* "Discussion" was renamed to "Comments"
* All project comments on Discussion were converted into WordPress Comments

Fixed:
* Better handling of long item names on frontend

Deprecated:
* upstream_disable_discussions()

= [1.12.5] - 2017-11-09 =

Added:
* Added new filter "upstream:project.onBeforeUpdateMissingMeta"
* Added method to render additional plugin update info if needed

Changed:
* UpStream Users user role no longer have "edit_others_projects" capability by default

Fixed:
* Fixed Completed/Closed Milestones, Tasks and/or Bugs counting as Overdue on frontend overview
* Fixed Bugs table not being ordered by Due Date by default
* Fixed some uncommon PHP errors being thrown after saving Tasks
* Fixed UpStream Users having access to any Project
* Fixed PHP warning being thrown on Project activity in the presence of any Reminder activity of the Email Notifications extension

= [1.12.4] - 2017-10-31 =

Added:
* Calendar View extension

= [1.12.3] - 2017-10-25 =

Fixed:
* Fixed project's permalink not appearing on form in admin
* Some PHP errors related to invalid timezones

= [1.12.2] - 2017-10-23 =

Added:
* Added new action on frontend to render custom HTML after the list on projects page

Changed:
* Discussion layout on frontend just got better
* Dropped use of progressbar js lib

Fixed:
* Fixed long titles overflowing tables on frontend
* Fixed screen reader texts appearing when they shouldn't
* Fixed Client/Client Users columns being displayed on /projects page even if Clients were disasbled
* Fixed top menu buttons on frontend not working on smaller screens
* Fixed missing parameter on wp_register_style function
* Fixed some items count widgets displaying fuzzy numbers
* Fixed some Client Users being able to access some private areas
* Fixed First Steps tutorial being shown to Client Users first time they enter a project
* Fixed Client Users list within Project not returning the right data
* Fixed progress bars fillings on frontend
* Fixed Tasks losing their Milestones after Disabling milestones on a project on save

= [1.12.1] - 2017-09-19 =

Changed:
* Changed overview boxes items order

Fixed:
* Attempt to fix some PHP errors

= [1.12.0] - 2017-09-18 =

Added:
* Added option to toggle categories for Projects and Clients
* Added option to toggle Clients/Client Users
* Added option to disable Discussions on particular Projects
* Added option to customize support link on frontend

Changed:
* Increased Discussion field width on admin
* Moved Project Details box to its own full width box on frontend
* Tasks and Bugs column headers were renamed to Title on frontend

Fixed:
* Fixed Projects breaking search results on frontend
* Fixed large images breaking the Project Activity tracker
* Fixed UpStream Users not being able to access Tasks/Bugs page on admin
* Fixed more strings missing from translation files

= [1.11.5] - 2017-08-31 =

Added:
* Added Requires PHP rule to readme.txt
* Added support for due date reminders through Email Notifications extension

= [1.11.4] - 2017-08-23 =

Fixed:
* Fixed UpStream Users being able to delete tasks that were not assigned to them
* Fixed remaining bug on Tasks dates always coming back with a value after saving them blank

= [1.11.3] - 2017-08-21 =

Update:
* Updated minimum requirements
* Start and End Dates for new Milestones are not autofilled anymore in admin

Fixed:
* Fixed xhtml attribute causing minor bug on Frontend Edit extension
* Fixed sidebar Tasks/Bugs counters taking into account disabled projects in admin
* Fixed empty avatar boxes bug
* Fixed Notes field layout on Tasks in admin
* Fix tasks titles returning to their default value after deleting a row

= [1.11.2] - 2017-08-08 =

Changed:
* Minor changes to readme.txt

= [1.11.1] - 2017-08-07 =

Added:
* Added the new UpStream Copy Project extension
* Added Settings action link on Plugins page

Changed:
* Minor text changes
* Removed outdated text from Project form
* Changed admin menu items order

Fixed:
* Fixed plugins update API's URL

= [1.11.0] - 2017-08-01 =

Changed:
* Client Users are now fully WordPress Users
* New layout for the Extensions page
* Small frontend clean up
* Clean up admin menu
* Changed redirect url after install
* Display Project Name and Logo options are now "checked" by default
* Removed "Visibility" field in the Publish box for Clients and Projects
* A lot code enhancements

Fixed:
* Task's title field is now required
* Make sure UpStream custom roles are removed on uninstall
* Enhanced support for internationalization
* Fix Milestone field being required for Tasks
* Fixed some typos

= [1.10.4] - 2017-07-20 =

Changed:
* Clearer Project timeframe date-strings

Fixed:
* Fixed bug that was causing items to lose their dates if edited on localized sites
* Empty columns on frontend tables now receive "none"
* Some code redundancies
* Some columns on frontend tables are no longer orderable

= [1.10.3] - 2017-07-12 =

Changed:
* Users are now capable of logging in via /projects page
* UpStream Users no longer can log in in a project using the client's password
* Metaboxes filters were moved from the top to the bottom of the box
* Appearance enhancements

Fixed:
* Fixed random logo appearing in /projects page
* Fixed bug giving some users a hard time logging in a project
* Fixed uncommon redirection bug after logging off on frontend
* Fixed bug causing some usernames to be blank in several places
* UpStream Users no longer can access projects in which they're not involved in
* Fixed some clients losing their password after saving the form

= [1.10.2] - 2017-07-02 =

Changed:
* Moved metaboxes filters to the bottom
* Client logo and Project name are now displayed by default on frontend login page (this can be changed on the options page)

Fixed:
* Internal code cleanup

= [1.10.1] - 2017-06-29 =

Added:
* UpStream now verifies if the environment where it is been installed on satisfies a set of minimum requirements
* Added two new options to UpStream's settings: Login Page Client Logo and Login Page Project Name

Changed:
* Project overview section is now hidden during adding new projects
* Code enhancements

Fixed:
* Fixed potential issues breaking some JS after the latest update
* Fixed password related functions errors on PHP versions prior to 5.5

= [1.10.0] - 2017-06-26 =

Added:
* Added filters on metaboxes on admin
* Added support to embeds on several TinyMCE instances
* Added support to the Email Notifications plugin

Changed:
* Code optimizations
* Readded Add Media button on several TinyMCE instances
* UpStream no longer use Bootstrap modals

Fixed:
* Fixed text overflowing from the Project Ativity section
* Fixed bug with some fields on frontend
* Fixed URLs references on frontend when WP was using non-default Permalink settings

= [1.9.1] - 2017-06-06 =

Changed:
* CMB2 Library was updated

Fixed:
* Fixed bug that was causing data loss on projects which was updated in any way by regular UpStream users

= [1.9.0] - 2017-06-06 =

Added:
* Added options to disable Milestones, Tasks, Bugs, Files and Discussions on all projects
* Added support for user avatars setted by [Custom User Profile Photo](https://wordpress.org/plugins/custom-user-profile-photo) plugin
* Added support for user avatars setted by [WP User Avatar](https://wordpress.org/plugins/wp-user-avatar) plugin

Changed:
* WYSIWYG editors are now teeny
* The whole login workflow was refactored due performance and security issues
* Make "Bugs/Tasks assigned to me" sections title more clearer
* Plugin's changelog now follows [Keep a Changelog](http://keepachangelog.com) pattern

Fixed:
* Make sure there's always a PHP session available for UpStream
* Fixed some users losing their sessions forcing them to log in every page they visit

Security:
* Clients project passwords are now hashed and handled properly

= [1.8.0] - 2017-05-15 =

Added:
* Milestones, Tasks, Bugs and Files can now be enabled/disabled for individual projects

Fixed:
* Fixed bug with menu Tasks and Bugs notification counter

= [1.7.0] - 2017-05-08 =

Added:
* Added "My Tasks" and "My Bugs" metaboxes in frontend so users might see exactly what was assigned to them
* Projects are now auto-saved after adding a new "Task", "Bug", "Discussion" or "File"
* UpStream now automatically uses users BuddyPress avatars if BuddyPress plugin is active in your WP instance

Changed:
* Dropped "Project Author" metabox
* Metaboxes now fills 100% width instead of being fixed

Fixed:
* Fixed items count bug in both "Tasks" and "Bugs" pages in /wp-admin
* Fixes bug with "Mine" filter in "Tasks" and  "Bugs" pages in /wp-admin
* A couple of other minor bugs were fixed overall
* Fixed non-numeric PHP warning

= [1.6.1] - 2017-05-02 =

Changed:
* Replaced Tasks Note textarea with a WYSIWYG editor

Fixed:
* Fixed UI bug in Project Description editor where all buttons position were messed up in Text Mode

= [1.6.0] - 2017-05-01 =

Added:
* Added a Description field to projects
* New Customizer add-on

Changed:
* Rename plugin title
* Update vendor libraries
* Code tested up to WordPress 4.7.4
* Replace some textarea fields with WYSIWYG editor instances in project form

Fixed:
* Fixed some frontend UI bugs
* Fixed bug that was preventing some special users from loggin in via frontend

= [1.5.4] - 2017-04-20 =

Fixed:
* Drop Style Setting page
* Fixed dates format in frontend
* Fixed incomplete projects metadata in frontend
* Fixed UI error in admin
* Fixed feedback messages for clients-related forms

= [1.5.3] - 2017-03-21 =

Changed:
* Update mobile styles on the frontend

= [1.5.2] - 2017-03-13 =

Changed:
* Update Translations

= [1.5.1] - 2017-02-22 =

Fixed:
* Errors when logged in as subscriber
* Deleting roles and capabilities on uninstall

= [1.5.0] - 2017-02-20 =

Added:
* Add new Style Settings page
* Add Messages column (showing the count) in projects list screen

Fixed:
* Issue with internationalized dates not being saved. Reverted to Y-m-d format

= [1.4.3] - 2017-02-17 =

Changed:
* UI improvements on frontend view
* UI improvements on project edit screen in admin

Fixed:
* Issue with counts of tasks if nobody assigned to task

= [1.4.2] - 2017-02-17 =

Fixed:
* Issue with Project Activity. Remove post_type check that is not required

= [1.4.1] - 2017-02-16 =

Added:
* Admin Edit Project UI. Add Task and Bug end date to title bar

= [1.4.0] - 2017-02-16 =

Added:
* Add Project Activity section
* Add upstream_user_item() function to get any user item

Changed:
* Admin Edit Project UI. Move progress bar and add statuses into title bar

Fixed:
* Bug with checking for client permissions

= [1.3.2] - 2017-02-14 =

Fixed:
* Issue with not loading activity class

= [1.3.1] - 2017-02-14 =

Fixed:
* Issue with wrong client logo displaying on All Projects page

= [1.3.0] - 2017-02-10 =

Added:
* Add option in settings to completely disable bugs
* Add help text to Client User email field
* Add link on frontend sidebar for files

Changed:
* Minor updates to styling on Client edit screen

Fixed:
* Add a check for multiple email addresses on client login

= [1.2.0] - 2017-02-10 =

Added:
* Redirect to settings page after activation
* Add guided tour for first Project

Changed:
* Update styling on settings pages
* Update styling on Project edit screen
* Make first Milestone always open when editing or adding project

Fixed:
* Add various extra code checks such as isset(), is_array() etc throughout plugin
* Email link on Client Users within project
* Issue with adding Discussions in admin area

= [1.1.1] - 2017-02-08 =

Added:
* Add banners on Extension settings page

Changed:
* Update CSS on Extension settings page

Fixed:
* Typo on Extension settings page

= [1.1.0] - 2017-02-07 =

Added:
* Included translations for en_AU
* Included translations for en_NZ

= [1.0.2] - 2017-02-07 =

Changed:
* Modify upstream_count_total() function to return 0 for the id if not found

Security:
* Add proper escaping on items within admin Tasks page

= [1.0.1] - 2017-02-03 =

Changed:
* Update links to documentation from within plugin page

Fixed:
* Undefined index within upstream_count_total() function

= [1.0.0] - 2017-01-20 =

* Initial release
