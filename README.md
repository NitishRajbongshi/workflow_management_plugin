# workflow_management_plugin
WordPress plugin to develop a plugin for workflow management.

# method to start the plugin
1. Install the WordPress in your system
2. Copy and paste all the plugin file in the /wp-content/plugins folder
2. create a composer.json file in the root directory
3. Write the following code in the composer.json file
<code>
{
  "require": {
    "firebase/php-jwt": "^6.5"
  }
}
</code>
4. Run the following commands in the root directory 
    i. composer install
    ii. composer require firebase/php-jwt