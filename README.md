HeyStaks Search
============================
Replace the built-in Magento Catalog Search with HeyStaks.


Installation
-------------

### Magento Connect
The HeyStaks module is currently in the process of being listed on Magento Connect.

In the meantime you can download the package [here](http://heystaks.studioforty9.com/HeyStaks_Core-0.1.3.tgz)

To install the module via Magento Connect, carry out the following steps:

1. Login to your Magento Admin and navigate to System -> Magento Connect -> Magento Connect Manager
2. Login to Magento Connect
3. Uncheck "Put store on the maintenance mode while installing/upgrading/backup creation"
4. Under "Direct package file upload" browse for the downloaded package by clicking the "Choose File" button
5. Press the "Upload" button. In the console screen, you should see that the module has been installed successfully.
6. Return to the Magento Admin. Navigate to System -> Cache Management. Press the "Flush Magento Cache" button to clear the cache. Logout and log back in to the Magento Admin.

### Composer
If you have not used Composer to install Magento modules before, we recommend following the following blog posts:

- [http://magebase.com/magento-tutorials/composer-with-magento/](http://magebase.com/magento-tutorials/composer-with-magento/)
- [http://alanstorm.com/php_composer_magento_tutorial](http://alanstorm.com/php_composer_magento_tutorial)

Once, you are comfortable with this process all you need to do is:

1. Add the following to your "repositories" attribute in your composer.json file:

   `{
		   	"type": "git",
   			"url": "https://github.com/StudioForty9/HeyStaks.git"
    }`

2. Add the following under the "required" attribute in your composer.json:

	`"heystaks/core":"dev-default"`

3. Run `composer update --no-dev` in a shell from your Magento root directory.
4. Clear the Magento cache

### modman

If you have not used modman to install Magento modules before, we recommend following the following tutorial:

- [https://github.com/colinmollenhour/modman/wiki/Tutorial](https://github.com/colinmollenhour/modman/wiki/Tutorial)

Once, you are comfortable with this process all you need to do is:

1. Run `modman init` from the Magento root directory if you have not already initialised modman for this Magento project
2. Run `modman clone HeyStaks_Core git@github.com:StudioForty9/HeyStaks.git`
3. Clear the Magento cache

Configuration
--------------
###General
* Active
	* Whether search results are returned from HeyStaks or not. 
* Test Mode
	* When in test mode, a set of radio buttons is added to the default search box. These buttons allow a user to specify whether to use the default Magento Search or the HeyStaks search. This is useful in order to be able to compare results and discover the improvements possible with HeyStaks and also to debug any anomalies that may occur.
	* If your theme has been customised these radio buttons may not appear. In this scenario, you can add "&search_type=heystaks" or "&search_type=magento" to the URL to simulate each type of search.
* OAuth Endpoint
 	* The URL for authentication to retrieve an access token for HeyStaks.
* Endpoint
	* The URL from which search results are retrieved and feedback sent to.
* Can Send Feedback?
	* Sometimes it can be beneficial to disable Feedback being sent to HeyStaks. For example, it might have a performance impact on the site or there may be unexpected consequences for the search index if erroneous feedback is being sent.
* Use Javascript API
	* Feedback is sent to HeyStaks when a customer selects a search result, adds to cart, purchases, etc. This communication is based on the Magento Event / Observer system. For Magento sites that use external caching systems, such as Varnish, these events will never be fired. Therefore, it is necessary to use Javascript based communication in these scenarios.
* Use Custom Autocomplete
	* The default Magento search auto-complete displays search term suggestions. The Custom Autocomplete displays search results with product name, image, price and short description, which is more useful to your customers. However, this functionality will normally require some CSS styling to be displayed correctly to the customer, so we recommend disabling initially and testing on a staging site.
* Use Magento Redirects and Synonyms
	* Many Magento sites that have been operating for a while will have many redirects and synonyms setup for popular search terms. Although advantageous in comparison with Magento's default search, leaving these enabled effectively robs HeyStaks of feedback on your most popular products and prevents it improving it's search ranking over time.
* Enable Logging
	* When enabled, the module will log all requests, responses, errors and cache hits/misses to {magento_root}/var/log/heystaks/. We recommend only turning this on when there is a problem with search as the logs can become quite big (i.e., multiple GB) on sites with high traffic and high search volume.
* Result Set Size
	* The number of results to be returned from HeyStaks. We recommend initially setting to a high value (e.g., 1000). This will not exactly match the number of results displayed to the customer as products that have been indexed by HeyStaks may be disable, hidden, out of stock in Magento.
 
###Authentication
* Application ID
	* As provided by HeyStaks when you open an account.
* Application Secret
 	* As provided by HeyStaks when you open an account.
* Admin Username
 	* As provided by HeyStaks when you open an account.
* Admin User Password
 	* As provided by HeyStaks when you open an account.

###Query Cache
* TTL
	* The "Time To Live" in seconds of results returned from HeyStaks for a particular query. We recommend setting this to a low value (e.g. 30 (i.e., 5 minutes)), as the HeyStaks results are constantly being updated based on what results are selected, added to cart, purchased, etc.

###Frontend Options
* Include 'Out of Stock' Products?
	* Whether products that are not salable should be included in the search results displayed to the customer.
* Show SKU in autocomplete?
	* Searching by SKU is particularly useful for merchants and on some types of ecommerce website. Whether an SKU is meaningful to a customer depends on the site but could be useful in certain scenarios.
* Message to Customer when 'OR' matching used
	* HeyStaks uses "AND" logic on the search query by default. If no results are found based on this logic, HeyStaks then reverts to "OR" logic. This field allows the merchant to display a message to the customer specifying that an _exact_ match was not found but that results were found for the individual elements of the query text. This is displayed as a "notice", using the standard Magento messaging system.

Indexing
---------

Once your HeyStaks API Account details have been entered in the configuration, you next need to add your products from Magento to the HeyStaks index. 

To do this, carry out the following steps:

1. Navigate to System -> Index Management in the Magento Admin
2. Click the "Reindex Data" link across from "Catalog Search Index"

This indexing process sends the product information to HeyStaks in batches of 100. When sending the information is completed by Magento it will take some minutes to complete the indexing by HeyStaks and for the site to be ready to search. How long this takes naturally depends on the size of the catalogue.


Demo Site
----------

A Demonstration Installation of the current version of Magento Community Edition using the Sample Data provided by Magento is available at the following URL:

[http://heystaks.studioforty9.com/](http://heystaks.studioforty9.com/)

You can also login to the Magento Admin to view the configuration settings.

- **Username:** demo
- **Password:** demo123


Description
------------
The following are points to note about the module's functionality:

* It is necessary that the Magento Cron is running correctly for the module to work correctly. If you need assistance in relation to this, please see [http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job](http://www.magentocommerce.com/wiki/1_-_installation_and_configuration/how_to_setup_a_cron_job)
* Where no results are returned from HeyStaks for whatever reason (e.g., network connectivity, new products that have not been indexed, etc.) the module reverts to the default Magento Search so customers should never be left without search results.


Limitations
------------
The module is currently limited in the following ways:

* The number of results available for each filter in the Layered Navigation is not correct.


Conflicts
-----------
The module may conflict with any 3rd party module that customises Search, Layered Navigation or Product Sorting. This conflict may be benign whereby either the HeyStaks module or the other 3rd party module will effectively be ignored in terms of the results shown. However, the conflict may cause errors on the page. In this scenario, we recommend you inform us of the conflicting module and disable on or other module.

Currently, the module is known to conflict with the list of modules below. We are actively seeking alternatives and ways to prevent conflicts in future. 

- ManaPro Filters 
	- The use of AJAX for filtering/sorting/paging/etc. This can be turned on/off in the configuration. 

Compatability
--------------
- Magento Community Edition >= 1.5

Uninstallation Instructions
----------------------------
### Magento Connect

1. Delete the file app/etc/modules/HeyStaks_Core.xml
2. Execute the following SQL:
   `DELETE FROM core_resource WHERE code = 'heystaks_setup';`
3. Remove all remaining extension files 
	* app/code/community/HeyStaks/
	* app/design/frontend/base/default/layout/heystaks.xml
	* app/design/frontend/base/default/template/heystaks/
	* js/heystaks/
	
### Composer
1. Remove the line `"heystaks/core":"dev-default"` from the composer.json file
2. Run `composer update --no-dev` from the Magento root directory
3. Execute the following SQL:
   `DELETE FROM core_resource WHERE code = 'heystaks_setup';`

### modman
1. Run `modman remove heystaks` from the Magento root directory
2. Execute the following SQL:
   `DELETE FROM core_resource WHERE code = 'heystaks_setup';`

Support
-------
If you have any issues with this extension, open an issue on GitHub (see URL above)

Contribution
------------
Any contributions are highly appreciated. The best way to contribute code is to open a
[pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
StudioForty9
[http://www.studioforty9.com](http://www.studioforty9.com)
[@sf9](https://twitter.com/sf9)

Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2014 StudioForty9