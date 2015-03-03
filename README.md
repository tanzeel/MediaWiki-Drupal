Versions:
Mediawiki 1.23
Drupal 7

Here are modules i used code from: https://github.com/naumenko/DrupalIntegration
https://www.drupal.org/project/mediawikiauth

Consult INSTALL.txt files in each Drupal module in packages for more details.

In "mediawiki files" folder there is one file named LocalSettings.php. I used that file for test environment to make this package work. Please consult that file too.

Here are features you can achieve using this package.


- Login MediaWiki -> Drupal
- Login Drupal -> MediaWiki
- Logout MediaWiki -> Drupal
- Logout Drupal -> MediaWiki
- Group -> Roles from MediaWiki -> Drupal
- Account Creation in MediaWiki -> Drupal
- Single Sign-On

Important:
Password import from MW to Drupal only works with setitng of
$wgPasswordSalt = false;
in MW settings file. (This is included in module readme.txt, just want to mention here as it is important)
