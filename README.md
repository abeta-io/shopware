# Abeta for Shopware 6

The official Shopware 6 plugin for Abeta.
Offer OCI and cXML PunchOut quickly and easily with Abeta. Connect with procurement systems / ERPs such as Coupa, Oracle and Sap Ariba. 
Increase the turnover of existing customers or acquire new customers with the help of B2B connections.

## Requirements

- Shopware 6.6

## Installation

#### Install via Composer

1. Go to your Shopware 6 root folder

2. Enter the following command to install the plugin:

   ```
   composer require abeta-io/shopware6
   ```

3. Enter the following commands to install and activate the plugin:

   ```
   bin/console plugin:refresh
   bin/console plugin:install --activate AbetaPunchOut
   bin/console cache:clear
   ```

4. Compile the storefront theme so the plugin's storefront assets are loaded:

   ```
   bin/console theme:compile
   ```

#### Install from GitHub

1. Download the zip package from https://github.com/abeta-io/shopware6 by clicking "Code" and selecting "Download ZIP" from the dropdown.

2. Create a custom/plugins/AbetaPunchOut directory in your Shopware 6 root folder.

3. Extract the contents from the "shopware6" zip and copy or upload everything to custom/plugins/AbetaPunchOut

4. Run the following commands from the Shopware 6 root folder to install and activate the plugin:

   ```
   bin/console plugin:refresh
   bin/console plugin:install --activate AbetaPunchOut
   bin/console cache:clear
   bin/console theme:compile
   ```
