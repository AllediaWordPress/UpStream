=== WordPress Project Management by UpStream ===
Contributors: upstreamplugin, deenison, andergmartins
Tags: project, manage, management, project management, project manager, wordpress project management, crm, client, client manager, tasks, issue tracker, bug tracker, task manager
Requires at least: 4.5
Tested up to: 5.2
Requires PHP: 5.6.20
Stable tag: 1.28.1
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
* PHP version 5.6.20 or greater

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


== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com)
and this project adheres to [Semantic Versioning](http://semver.org).

= [1.28.1] - 2019-09-24 =
* Fixed assignment email bug

= [1.28.0] - 2019-09-20 =
* Fixed various bugs with timezones
* Added emailing users if a comment is placed on their item
* Added email notifications on a certain date
* Fixed various bugs with email notifications

= [1.27.3] - 2019-09-09 =
* Fixed item 891 - health check creates errors
* Fixed items 886-890 - permissions issues

= [1.27.2] - 2019-09-02 =
* Fixed conflict with CMB2
* Fixed incompatibility with new version of jQuery

= [1.27.0] - 2019-08-19 =
* Fixed PHP errors related to tasks and bugs (line 581 bug);
* Fixed 0s showing up in frontend under the Assigned To column if nobody is assigned;
* Fixed error in up_project_functions.php;
* Fixed dropdown for status and progress in admin dashboard;
* New projects now properly show 0 tasks and bugs count;
* Fixed date format bug that was breaking popup modal windows;
* Wrapped usernames in project page;
* Allow frontend to be shown even when there are no projects;

= [1.26.0] - 2019-07-29 =

* Fixed date sorting in the projects list;
* Added optional category support for milestones;
* Fixed the return value of upstream_disable_bugs() to always return a boolean value;
* Removed the link from the "All Projects" menu in the front-end. Now it opens the submenu correctly;
* Added color to milestones and milestone categories. Milestone category's color is used as default color for child milestones;
* Added option to show all the projects in the left sidebar on the front-end;
* Fixed the return value of getProjectId() to always return an integer;
* Added methods to the Milestone class: isInProgress, isUpComing, isCompleted;
* Fixed datepicker's z-index in the front-end;
* Added the project id as passed argument to the action upstream_single_project_section_*;
* Added action to enqueue assets in the front-end;
* Fixed an error in the front-end related to UpStream_View class not being found;
* Added action to inject custom content before the front-end pagel
* Added new filters to whitelist styles or scripts in the front-end, avoiding to dequeue them;
* Fixed Clients admin page to not break when Custom Fields add-on is not installed;
* Changed notifications to email all project's users for new comments/discussions;

= [1.25.1] - 2019-07-24 =

* Fixed the default value for the Show Users Names setting;

= [1.25.0] - 2019-07-24 =

* Added automatic status change for tasks based on specific percentage and vice-versa;
* Fixed number of active tasks for the user in the frontend;
* Added option to show usernames instead of avatars in the frontend;
* Added custom fields to clients;

= [1.24.4] - 2019-06-26 =

* Fixed the order of milestones on tables;
* Fixed Start and End date fields allowing both to have the same date;
* Fixed project progress for tasks without a milestone;
* Fixed project sorting by progress;
* Fixed overview data when project is in draft status;
* Fixed the filter by client to accept space;
* Fixed output date to count the timezone offset;
* Implemented 'export-as' attribute for table columns allowing to split and export data from columns with more than one information;
* Fixed sortable behavior for Start and End columns;
* Fixed consistency in the way we sort tasks and bugs in the admin, using drag-and-drop like on milestones;
* Added a text to display how many search results were returned in the front-end;
* Added a lock in the frontend when another user is editing a task or milestone to avoid overwrite changes [by oraclerob];


= [1.24.3] - 2019-05-30 =

* Fixed PHP warning: array_merge(): Argument #2 is not an array;
* Fixed the discussion metabox being displayed by default in the Milestone screen;
* Changed the way to reorder Milestones in the admin, removing the buttons and adding a drag-and-drop behavior;
* Fixed support for multisites;
* Fixed missed users when exporting projects;
* Fixed PHP warning regarding undefined stdClass::$delete_posts in the Milestones screen;
* Fixed PHP warning when a milestone is created without any assigned user;
* Replaced "Tick" with "Check" on some strings;
* Fixed image in the special discount box;

= [1.24.2] - 2019-05-22 =

* Added column for Milestone Color;
* Added 2 new filters to control weather to display the overview and detail sections: upstream_display_overview_section, upstream_display_details_section;
* Fix the sortable behavior for milestones in the frontend;
* Fixed datepicker icons;
* Fixed datepicker fields to avoid invalid dates in milestones;
* Fixed the JS and CSS assets being loaded in too many admin pages;
* Fixed PHP errors;
* Fixed Milestone ordering;

= [1.24.1] - 2019-05-06 =

* Fixed the migration of existent legacy milestones making sure all projects are migrated;
* Fixed the state of the Start Migration button in the settings page during a migration;
* Fixed a PHP warning for old corrupted milestone data;
* Fixed the list of available users in the Client Users page;
* Fixed the total numbers in the project's summary;
* Lock Twig version on 1.38.4;

= [1.24.0] - 2019-04-29 =

* Added option field to select the user roles that can be active inside projects. Those users can be assigned Milestones, Tasks and Bugs and own projects;
* Added hooks allowing to customize the editor buttons;
* Added custom Milestones per project. They are not set globally anymore;
* Removed the global Milestone list from the settings tabs;
* Updated Twig and Polyfill-ctype libraries;
* Added new hooks to filter Tinymce plugins, external plugins and toolbar buttons, allowing to add custom buttons to the editors;
* Fixed white line displayed in the project title when title has more than one line;

= [1.23.2] - 2019-03-07 =

* Fixed how we load the sidebar template in the frontend, allowing themes to provide a custom template;
* Updated the subscription banner for a discount of 20% on subscribing;
* Fixed the compatibility issue with Health Check to pass all the tests;
* Fixed the URL for assets on systems that rely on Bedrock or other custom WordPress folders structure;
* Fixed a JS error about undefined datepicker object in the frontend on some themes;

= [1.23.1] - 2019-01-15 =

* Fixed PHP error when set_time_limit function is disabled;
* Fixed JS error when datepicker.dates is not defined;
* Added button to cleanup plugin's update data cache;
* Updated the description for the maintenance button;
* Added minor improvement to style of menu items in the frontend;
* Added missed dependency jquery-ui-sortable for the upstream script;
* Removed reference to the .css.map file;
* Added a new optional parameter to the function upstream_date_unixtime;

= [1.23.0] - 2018-12-12 =

* Added a new button to the maintenance section to refresh the count of tasks and list of members in all projects;
* Added additional information to the debug page: PHP version, OS, WordPress version and list of plugins and their activation status;
* Added new action to the front-end sidebar menu: upstream_sidebar_menu;
* Added new actions after project's panels: upstream_single_project_after_milestones, upstream_single_project_after_tasks, upstream_single_project_after_bugs, upstream_single_project_after_files, upstream_single_project_after_discussion;
* Added persistence state for collapsible box in the project's front-end;
* Added sortable behavior for the project's panels in the front-end;
* Added tag IDs to the menu items in the front-end: nav-projects, nav-milestones, etc;
* Deprecated action "upstream:frontend.project.renderAfterDetails". Use the new action "upstream_single_project_sections";
* Fixed code style on PHP files;
* Fixed columns of data coming from custom fields in the front-end;
* Fixed date localization in UpStream, using the date_i18n function;
* Fixed duplicated message for "not found" items in the front-end table when filtering data;
* Fixed icons for filters for custom fields;
* Fixed JS error regarding undefined "ajaxurl" variable;
* Fixed sorting projects by status in the front-end;
* Fixed the counter of open tasks on milestones;
* Fixed the HTML header we print in the front-end;
* Fixed the persistence of sorted columns in front-end tables;
* Fixed the way we load the plugin's text domain;
* Fixed the write method in the UpStream_Debug class adding a conditional to do nothing if debug is disabled;
* Optimized the methods to get project's comments;
* Removed minimum width for the columns in the project list to fit columns from custom fields;
* Updated file references in the POT file, and added terms for date translation in the front-end;


= [1.22.2] - 2018-10-24 =

* Fixed updates to do not reset capabilities for UpStream user roles every time a package is installed;
* Fixed updates to only run upgrade routines when it is really an update, ignoring them on fresh installs or re-installations;
* Fixed updates to do not redirect to the projects after installing. It will only redirect after a fresh install;
* Fixed basic capabilities for UpStream's Users and Client Users so them are able to see and edit all the fields by default;
* Fixed a typo in the capability "bug_files_field" added for admins. It was setting "bug_file_field" instead;
* Fixed a PHP warning in the admin when TinyMCE don't have the "selector" index;
* Changed the button to reset capabilities, splitting it in 4 buttons, one per user role: Administrator, UpStream Managers, UpStream Users, UpStream Client Users;

= [1.22.1] - 2018-10-18 =

* Fixed ordering for Milestone, Tasks and Bugs columns in the front-end;
* Fixed date format in datepicker fields when the date format has "S";
* Fixed default CSS classes for the body element in the front-end page;
* Fixed PHP warning when status is not defined;
* Added option to order the table of tasks by status;
* Added action link in the plugins list to set the license key;
* Added warning in the plugin list if there is no license key set, or if automatic update is not available;
* Added specific classes and data-column attributes to the cells of data tables in the front-end, to allow customize the style or hide them using CSS;
* Added CSS class to the export buttons;

= [1.22.0] - 2018-10-03 =

* Fixed filter for custom fields in projects in the front-end;
* Fixed Add Comment label for translators and updated the POT file;
* Fixed capabilities for Project's fields;
* Fixed compatibility with 3rd party JavaScript scripts and the load event method. It was missed sometimes, so now we use ".on('load', ...)";
* Fixed client filters removing them when Clients are disabled;
* Fixed labels "statuses" and "owners" for translators - updated POT file;
* Added option in the General Settings to filter closed items/projects by default;
* Added option in the General Settings to archive closed items/projects in the front-end. Those items won't be loaded, so can't be filtered;
* Added actions specific for adding new columns to the project's table in the front-end;
* Added missed capabilities to roles;

= [1.21.1] - 2018-09-19 =

* Fixed links in comments to not remove the target and other attributes;
* Fixed a syntax error in the HTML of a metabox properly closing a P tag;
* Fixed license key activation and upgrade form when installed alongside PublishPress - requires to update PublishPress as well;
* Fixed wrong URL for assets on Windows machines;
* Added action hook when the project meta is being updated;
* Updated POT file;

= [1.21.0] - 2018-09-06 =

* Fixed comments not being sent if the user have disabled editors - checking the option "Disable the visual editor when writing" on his profile;
* Fixed stripped HTML tags on projects' comments - added basic tags like P, BR, STRONG, EM, SPAN, DEL, UL, OL and LI;
* Fixed HTML content sent in emails sent for comments' notifications. The email now is sent as text/html;
* Fixed PHP warning in the admin when there is no milestones in the project;
* Fixed PHP warning about undefined indexes: before_row and after_row;
* Refactored the license management page, upgrade links and subscription form, based on the Alledia's plugin framework;
* Updated the POT file for translators;

= [1.20.2] - 2018-08-21 =

* Fixed missed debug statement in the code;
* Fixed fatal error when date fields are empty and a task is saved in the front-end;
* Fixed images in the comments, adding an option and custom capability (upstream_comment_images) for controlling who can add images. All roles will be selected by default;
* Fixed comments when they contain only images, without any text;
* Fixed the field to assign tasks to users on new tasks for some sites where the field was blocked;
* Fixed JavaScript error that prevents to select new assigner for tasks in some sites;

= [1.20.1] - 2018-08-14 =

* Fixed the filter by statuses in the tasks and bugs page;
* Fixed invalid dates result of some wrong timezone calculation;
* Fixed the verification for the Poopy sandbox sites before load its CSS file in the front-end;
* Fixed error in the front-end when the current user is not defined in the session;

= [1.20.0] - 2018-07-31 =

* Added option in the general settings to pre-select all client's users by default, after select a client;
* Fixed saving project data in the front-end;
* Fixed the loaded value of color picker fields in the front-end;
* Fixed the style for "none" value of fields in the tables on the front-end;
* Fixed line break on field values in the tables on the front-end;
* Fixed JS error related to "invalid field not focusable" for hidden fields in the back-end form validation;
* Fixed default value for fields in the front-end;
* Fixed method to return project's data for add-ons;

= [1.19.1] - 2018-07-11 =

* Fixed the custom label for Discussions;
* Added option top select roles which can see all media;
* Fixed the filter for statuses for tasks;
* Added the link to the list of projects to the main menu item;
* Fixed PHP error when project members is not an array;
* Updated the .pot file for translators;
* Improved some text;
* Added option to enable debug and log on UpStream settings for debugging sessions;

= [1.19.0] - 2018-07-04 =

* Improved the style in the extensions page;
* Highlighter upgrade and extensions links;
* Added form and message in the settings page to subscribe and earn 20% off and ask for review;

= [1.18.4] - 2018-06-22 =

* Fixed style for the admin bar which was being displayed in the bottom-left;

= [1.18.3] - 2018-06-20 =

* Fixed the admin bar, removing code that was hiding it. It is displayed in the front-end now;
* Fixed the discussion menu item in the sidebar when discussion is disabled;
* Updated cmb2 library;

= [1.18.2] - 2018-06-07 =

* Fixed a PHP warning when there is no client users in the project;
* Fixed a PHP warning about wrong data type on the second param in the in_array function;
* Fixed the height of navigation buttons buttons of the calendar;
* Fixed the count of tasks and bugs in the project in the admin and front-end;
* Fixed the form validation error highlighting displaying more relevant and visible error message;
* Fixed hardcoded labels for "Discussion" and added field to customize the string;
* Fixed hardcoded labels for "Client";
* Fixed the milestone titles in the activity box;
* Removed the list of projects from the sidebar;
* Removed the vertical line from the menu in the sidebar;
* Added new icon for the project in the sidebar;
* Declared the method setContentHeight in the global scope in JS for add-ons;
* Removed arrow icon from menus in the sidebar;
* Added new hooks to display specific views in the front-end, for add-ons;

= [1.18.1] - 2018-05-29 =

* Renamed templates/assets/lib folder to templates/assets/libraries;

= [1.18.0] - 2018-05-29 =

* Fixed the rewrite rules after activating/deactivating the plugin, flushing the rules to refresh;
* Fixed the method to format date using the correct timezone;
* Fixed a typo in the footer;
* Added option to change the projects and client URL in the front-end;
* Added new JS library for select fields, chosen - available for add-ons;
* Added method to convert date to unixtime: upstream_date_unixtime;
* Added method to return an array with a list of client's users: upstream_get_all_client_users;
* Added new actions;* Updated the .pot file;
* Removed little chain icon from the list of projects;

= [1.17.0] - 2018-04-26 =

* Added action "upstream:frontend.project.details.after_title";
* Increased spacing between filters section and data rows within Projects in wp-admin;
* Fixed major architecture flaw where Projects were losing track of Project Statuses, Milestones Statuses, Tasks Statuses, Bugs Statuses/Severities if they were changed through UpStream settings;
* Fixed some Projects description not being rendered as HTML;
* Small text update on the "Project Progress Icons" options;
* Fixed Notes/Description losing their formatting on frontend;
* Fixed bug where it was impossible to expand table rows on frontend browsing through small-screens;

= [1.16.4] - 2018-04-18 =

* Increased maximum execution time for frontend scripts;
* Minor performance enhancements on front end pages;
* Fixed uncommon bug where jQuery UI DatePicker plugin was being loaded on frontend;
* Fixed filters on admin project page that can have multiple values;
* Fixed permissions check failing for items having multiple assignees;
* Fixed PHP warnings;

= [1.16.3] - 2018-04-02 =

* Fixed comments not being displayed anymore;

= [1.16.2] - 2018-03-27 =

* Added option under user's profile to choose whether to be notified when someone replies to his comments;
* Users are now notified about comment replies;
* Fixed yet another error with malconversion of some time zones;

= [1.16.1] - 2018-03-13 =

* Changed "Disable Project Overview" option label to "Project Progress Icons";
* Removed deprecated methods on v1.15.0;
* Fixed avatar infinite multiplication after adding new items to a Project in wp-admin;
* Fixed recent PHP warnings thrown under PHP 7.2;

= [1.16.0] - 2018-03-08 =

* Users can be assigned to Files;
* Client Users can also be assigned to Milestones/Tasks/Bugs/Files;
* Managers can now assign multiple users to Milestones, Tasks and Bugs;
* Minor text changes on Start/End Date filters;
* Fixed error message shown on frontend after changing Severity/Status/Milestone names;
* Fixed errors while adding/changing Client logo in admin;

= [1.15.1] - 2018-02-22 =

* "Title" search fields placeholders are not individually i18n scoped anymore;
* Fixed 404 redirects after login/logout in some environments;
* Fixed Start/End/Due Date fields not always being stored as GMT/UTC;

= [1.15.0] - 2018-02-15 =

* Added Categories, Status, Clients, Title filters for Projects on frontend;
* Added Milestone, Assignee, Star and End Dates filters for Milestones;
* Added Title, Assignee, Status, Milestone, Star and End Dates filters for Tasks;
* Added Title, Assignee, Severity, Status, Due Date filters for Bugs;
* Added Title, Uploader, Upload Date filters for Files;
* Added "Owner" and "Client" filter to the admin Projects list;
* Project Owners will receive comment notifications;
* Assigned users and creators now receive notifications about comments on their item;
* Users can now filter metaboxes/tables data using multiple filters at once;
* We're slowly moving towards using Select2 lib across the whole plugin;
* Frontend Date filters now use a new Date Picker js lib;
* Replaced wp_verify_nonce in favor of check_ajax_referer on the comments AJAX endpoints;
* Minor text changes;
* Update year in copyright info;

Deprecated:
* Within UpStream_Metaboxes_Projects class: getStatusFilterHtml, getSeverityFilterHtml, getFiltersHeaderHtml, getFiltersFooterHtml, getMilestoneFilterHtml;
* Frontend tables no longer use Datatable lib due lack of flexibility and performance issues;
* Fix Comments label missing from Screen Options pulldown in the Projects page;
* Fixed Status filter in Projects admin list getting reseted after being selected;
* Fixed Project author not receiving comment notifications;
* Fixed Start/End Dates intervals;

= [1.14.1] - 2018-02-12 =

* Fixed CMB2 not being loaded correctly in a multisite environment;
* Fixed some DB calls triggering errors in multisite environments;

= [1.14.0] - 2018-01-31 =

* Tags can now be assigned to Projects;
* Added "Disable Project Overview" option;
* Added "Disable Project Details" option;
* Auto scroll to particular comments via URL;
* "Comments on <section>" options labels were renamed to "Disable Discussion on <section>";
* Some options were grouped for better UX;
* Fixed a couple of strings not being translated as they should;

= [1.13.7] - 2018-01-26 =

* Remove notice about recent changes made on Clients;
* Remove deprecated code;
* Fixed some Comments tabs not working on admin;
* Fixed some potential PHP errors and warnings;

= [1.13.6] - 2018-01-15 =

* Users can no longer be added via Clients page;

Deprecated:
* Legacy Users migration class/functions/methods were marked as deprecated;
* Removed upstream_disable_discussions() deprecated function;
* Fixed conflict with Sliced Invoices plugin;
* Fixed bug where items comments were not being retrieved on admin;
* Fixed some dates being converted when they shouldn't;
* Fixed long user names overflowing on frontend sidebar;
* Fixed Notes/Description fields losing their format on frontend;
* Fixed not being able to assign existent users to Clients;

= [1.13.5] - 2018-01-04 =

* Changed no data message for consistency across sections on frontend;
* Update CMB2 to v2.3.0;
* Legacy Client Users Migration script and related methods were marked as deprecated and will be removed on future releases;
* Fixed some bad redirects relying on home_url() instead of site_url();
* Removed stray "none" text from Discussion section in admin;
* Fixed some users not being able to save/update Projects on admin;
* Fixed some PHP warnings thrown while adding comments;

= [1.13.4] - 2017-12-29 =

* Fixed sidebar icon on admin in some pages;

= [1.13.3] - 2017-12-29 =

* Fixed white screen on settings page;
* Fixed CMB2 loading bug;

= [1.13.2] - 2017-12-29 =

* Added new extension: Custom Fields;
* Added new filter that allow custom post types to load CMB2 in admin;
* Display none to empty Notes/Description/Comments fields;
* Removed "Settings" label from settings sub menu items;
* CMB2 lib was updated to v2.2.6.2;
* Lang files cleanup;
* Project Comments section was renamed back to Discussion;
* Fixed some assets being loaded on every page;
* Fixed missing Discussion link on the frontend sidebar;

= [1.13.1] - 2017-12-07 =

* Fix Bugs widget on frontend using Tasks statuses labels instead;
* Fixed wrong redirects for some non UpStream users;
* Fixed some users not being able to access their own posts;
* Fixed post listing being empty for some users outside UpStream's scope;
* Fixed potential PHP error on frontend;
* Fixed Description/Notes losing line breaks on frontend display;

= [1.13.0] - 2017-11-30 =

* Added support for comment replies;
* Added Discussion/Comments to Milestones, Tasks, Bugs, Files;
* "Discussion" was renamed to "Comments";
* All project comments on Discussion were converted into WordPress Comments;
* Better handling of long item names on frontend;

Deprecated:
* upstream_disable_discussions();

= [1.12.5] - 2017-11-09 =

* Added new filter "upstream:project.onBeforeUpdateMissingMeta";
* Added method to render additional plugin update info if needed;
* UpStream Users user role no longer have "edit_others_projects" capability by default;
* Fixed Completed/Closed Milestones, Tasks and/or Bugs counting as Overdue on frontend overview;
* Fixed Bugs table not being ordered by Due Date by default;
* Fixed some uncommon PHP errors being thrown after saving Tasks;
* Fixed UpStream Users having access to any Project;
* Fixed PHP warning being thrown on Project activity in the presence of any Reminder activity of the Email Notifications extension;

= [1.12.4] - 2017-10-31 =

* Calendar View extension;

= [1.12.3] - 2017-10-25 =

* Fixed project's permalink not appearing on form in admin;
* Some PHP errors related to invalid timezones;

= [1.12.2] - 2017-10-23 =

* Added new action on frontend to render custom HTML after the list on projects page;
* Discussion layout on frontend just got better;
* Dropped use of progressbar js lib;
* Fixed long titles overflowing tables on frontend;
* Fixed screen reader texts appearing when they shouldn't;
* Fixed Client/Client Users columns being displayed on /projects page even if Clients were disasbled;
* Fixed top menu buttons on frontend not working on smaller screens;
* Fixed missing parameter on wp_register_style function;
* Fixed some items count widgets displaying fuzzy numbers;
* Fixed some Client Users being able to access some private areas;
* Fixed First Steps tutorial being shown to Client Users first time they enter a project;
* Fixed Client Users list within Project not returning the right data;
* Fixed progress bars fillings on frontend;
* Fixed Tasks losing their Milestones after Disabling milestones on a project on save;

= [1.12.1] - 2017-09-19 =

* Changed overview boxes items order;
* Attempt to fix some PHP errors;

= [1.12.0] - 2017-09-18 =

* Added option to toggle categories for Projects and Clients;
* Added option to toggle Clients/Client Users;
* Added option to disable Discussions on particular Projects;
* Added option to customize support link on frontend;
* Increased Discussion field width on admin;
* Moved Project Details box to its own full width box on frontend;
* Tasks and Bugs column headers were renamed to Title on frontend;
* Fixed Projects breaking search results on frontend;
* Fixed large images breaking the Project Activity tracker;
* Fixed UpStream Users not being able to access Tasks/Bugs page on admin;
* Fixed more strings missing from translation files;

= [1.11.5] - 2017-08-31 =

* Added Requires PHP rule to readme.txt;
* Added support for due date reminders through Email Notifications extension;

= [1.11.4] - 2017-08-23 =

* Fixed UpStream Users being able to delete tasks that were not assigned to them;
* Fixed remaining bug on Tasks dates always coming back with a value after saving them blank;

= [1.11.3] - 2017-08-21 =

* Updated minimum requirements;
* Start and End Dates for new Milestones are not autofilled anymore in admin;
* Fixed xhtml attribute causing minor bug on Frontend Edit extension;
* Fixed sidebar Tasks/Bugs counters taking into account disabled projects in admin;
* Fixed empty avatar boxes bug;
* Fixed Notes field layout on Tasks in admin;
* Fix tasks titles returning to their default value after deleting a row;

= [1.11.2] - 2017-08-08 =

* Minor changes to readme.txt;

= [1.11.1] - 2017-08-07 =

* Added the new UpStream Copy Project extension;
* Added Settings action link on Plugins page;
* Minor text changes;
* Removed outdated text from Project form;
* Changed admin menu items order;
* Fixed plugins update API's URL;

= [1.11.0] - 2017-08-01 =

* Client Users are now fully WordPress Users;
* New layout for the Extensions page;
* Small frontend clean up;
* Clean up admin menu;
* Changed redirect url after install;
* Display Project Name and Logo options are now "checked" by default;
* Removed "Visibility" field in the Publish box for Clients and Projects;
* A lot code enhancements;
* Task's title field is now required;
* Make sure UpStream custom roles are removed on uninstall;
* Enhanced support for internationalization;
* Fix Milestone field being required for Tasks;
* Fixed some typos;

= [1.10.4] - 2017-07-20 =

* Clearer Project timeframe date-strings;
* Fixed bug that was causing items to lose their dates if edited on localized sites;
* Empty columns on frontend tables now receive "none";
* Some code redundancies;
* Some columns on frontend tables are no longer orderable;

= [1.10.3] - 2017-07-12 =

* Users are now capable of logging in via /projects page;
* UpStream Users no longer can log in in a project using the client's password;
* Metaboxes filters were moved from the top to the bottom of the box;
* Appearance enhancements;
* Fixed random logo appearing in /projects page;
* Fixed bug giving some users a hard time logging in a project;
* Fixed uncommon redirection bug after logging off on frontend;
* Fixed bug causing some usernames to be blank in several places;
* UpStream Users no longer can access projects in which they're not involved in;
* Fixed some clients losing their password after saving the form;

= [1.10.2] - 2017-07-02 =

* Moved metaboxes filters to the bottom;
* Client logo and Project name are now displayed by default on frontend login page (this can be changed on the options page);
* Internal code cleanup;

= [1.10.1] - 2017-06-29 =

* UpStream now verifies if the environment where it is been installed on satisfies a set of minimum requirements;
* Added two new options to UpStream's settings: Login Page Client Logo and Login Page Project Name;
* Project overview section is now hidden during adding new projects;
* Code enhancements;
* Fixed potential issues breaking some JS after the latest update;
* Fixed password related functions errors on PHP versions prior to 5.5;

= [1.10.0] - 2017-06-26 =

* Added filters on metaboxes on admin;
* Added support to embeds on several TinyMCE instances;
* Added support to the Email Notifications plugin;
* Code optimizations;
* Readded Add Media button on several TinyMCE instances;
* UpStream no longer use Bootstrap modals;
* Fixed text overflowing from the Project Ativity section;
* Fixed bug with some fields on frontend;
* Fixed URLs references on frontend when WP was using non-default Permalink settings;

= [1.9.1] - 2017-06-06 =

* CMB2 Library was updated;
* Fixed bug that was causing data loss on projects which was updated in any way by regular UpStream users;

= [1.9.0] - 2017-06-06 =

* Added options to disable Milestones, Tasks, Bugs, Files and Discussions on all projects;
* Added support for user avatars setted by [Custom User Profile Photo](https://wordpress.org/plugins/custom-user-profile-photo) plugin;
* Added support for user avatars setted by [WP User Avatar](https://wordpress.org/plugins/wp-user-avatar) plugin;
* WYSIWYG editors are now teeny;
* The whole login workflow was refactored due performance and security issues;
* Make "Bugs/Tasks assigned to me" sections title more clearer;
* Plugin's changelog now follows [Keep a Changelog](http://keepachangelog.com) pattern;
* Make sure there's always a PHP session available for UpStream;
* Fixed some users losing their sessions forcing them to log in every page they visit;

Security:
* Clients project passwords are now hashed and handled properly;

= [1.8.0] - 2017-05-15 =

* Milestones, Tasks, Bugs and Files can now be enabled/disabled for individual projects;
* Fixed bug with menu Tasks and Bugs notification counter;

= [1.7.0] - 2017-05-08 =

* Added "My Tasks" and "My Bugs" metaboxes in frontend so users might see exactly what was assigned to them;
* Projects are now auto-saved after adding a new "Task", "Bug", "Discussion" or "File";
* UpStream now automatically uses users BuddyPress avatars if BuddyPress plugin is active in your WP instance;
* Dropped "Project Author" metabox;
* Metaboxes now fills 100% width instead of being fixed;
* Fixed items count bug in both "Tasks" and "Bugs" pages in /wp-admin;
* Fixes bug with "Mine" filter in "Tasks" and  "Bugs" pages in /wp-admin;
* A couple of other minor bugs were fixed overall;
* Fixed non-numeric PHP warning;

= [1.6.1] - 2017-05-02 =

* Replaced Tasks Note textarea with a WYSIWYG editor;
* Fixed UI bug in Project Description editor where all buttons position were messed up in Text Mode;

= [1.6.0] - 2017-05-01 =

* Added a Description field to projects;
* New Customizer add-on;
* Rename plugin title;
* Update vendor libraries;
* Code tested up to WordPress 4.7.4;
* Replace some textarea fields with WYSIWYG editor instances in project form;
* Fixed some frontend UI bugs;
* Fixed bug that was preventing some special users from loggin in via frontend;

= [1.5.4] - 2017-04-20 =

* Drop Style Setting page;
* Fixed dates format in frontend;
* Fixed incomplete projects metadata in frontend;
* Fixed UI error in admin;
* Fixed feedback messages for clients-related forms;

= [1.5.3] - 2017-03-21 =

* Update mobile styles on the frontend;

= [1.5.2] - 2017-03-13 =

* Update Translations;

= [1.5.1] - 2017-02-22 =

* Errors when logged in as subscriber;
* Deleting roles and capabilities on uninstall;

= [1.5.0] - 2017-02-20 =

* Add new Style Settings page;
* Add Messages column (showing the count) in projects list screen;
* Issue with internationalized dates not being saved. Reverted to Y-m-d format;

= [1.4.3] - 2017-02-17 =

* UI improvements on frontend view;
* UI improvements on project edit screen in admin;
* Issue with counts of tasks if nobody assigned to task;

= [1.4.2] - 2017-02-17 =

* Issue with Project Activity. Remove post_type check that is not required;

= [1.4.1] - 2017-02-16 =

* Admin Edit Project UI. Add Task and Bug end date to title bar;

= [1.4.0] - 2017-02-16 =

* Add Project Activity section;
* Add upstream_user_item() function to get any user item;
* Admin Edit Project UI. Move progress bar and add statuses into title bar;
* Bug with checking for client permissions;

= [1.3.2] - 2017-02-14 =

* Issue with not loading activity class;

= [1.3.1] - 2017-02-14 =

* Issue with wrong client logo displaying on All Projects page;

= [1.3.0] - 2017-02-10 =

* Add option in settings to completely disable bugs;
* Add help text to Client User email field;
* Add link on frontend sidebar for files;
* Minor updates to styling on Client edit screen;
* Add a check for multiple email addresses on client login;

= [1.2.0] - 2017-02-10 =

* Redirect to settings page after activation;
* Add guided tour for first Project;
* Update styling on settings pages;
* Update styling on Project edit screen;
* Make first Milestone always open when editing or adding project;
* Add various extra code checks such as isset(), is_array() etc throughout plugin;
* Email link on Client Users within project;
* Issue with adding Discussions in admin area;

= [1.1.1] - 2017-02-08 =

* Add banners on Extension settings page;
* Update CSS on Extension settings page;
* Typo on Extension settings page;

= [1.1.0] - 2017-02-07 =

* Included translations for en_AU;
* Included translations for en_NZ;

= [1.0.2] - 2017-02-07 =

* Modify upstream_count_total() function to return 0 for the id if not found;

Security:
* Add proper escaping on items within admin Tasks page;

= [1.0.1] - 2017-02-03 =

* Update links to documentation from within plugin page;
* Undefined index within upstream_count_total() function;

= [1.0.0] - 2017-01-20 =

* Initial release;
