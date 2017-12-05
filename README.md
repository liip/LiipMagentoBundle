UNMAINTAINED
============

This bundle is no longer maintained. Feel free to fork it if needed.

MagentoBundle
=============

Integrate Magento into Symfony2 applications.

The Bundle is still a work in progress but the goal is to be able
to talk to Magento from inside Symfony. This means that Magento
app is initialized inside Symfony2, to share a single session,
enable reusing Magento login in Symfony2 and reading content and
layout from Magento.

Installation
============

1.  Add the following lines in your deps file:
    
    ```
    [LiipMagentoBundle]
        git=http://github.com/liip/LiipMagentoBundle.git
        target=/bundles/Liip/MagentoBundle
    ```

2.  Run the vendors script:
    
    ```
    $ php bin/vendors install
    ```

3.  Add the Liip namespace to your autoloader:
    
    ```
    // app/autoload.php
    $loader->registerNamespaces(array(
        'Liip' => __DIR__.'/../vendor/bundles',
        // your other namespaces
    ));
    ```

4.  Add the bundle to your application kernel:
    
    ```
    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Liip\MagentoBundle\LiipMagentoBundle(),
            // ...
        );
    }
    ```

5.  Configure the bundle
    
    See *Configuration*
    

6.  Fix the Magento autoloader

    ```
    $ cd $MAGENTO_DIR
    $ patch -p0 < $SYMFONY_DIR/vendor/bundles/Liip/MagentoBundle/magento-autoloader.patch
    ```


Configuration
============

```
# app/config/config.yml
framework:
    session:
        # use the Magento session handler
        storage_id: liip_magento.session.storage

liip_magento:
    # path to the Mage.php file
    mage_file:  %kernel.root_dir%/../../magento/app/Mage.php
    # not for all store resolvers, mapping to store code
    store_mappings:
        de: de
        en: en
        fr: en
```

```
# app/config/security.yml
security:
    factories:
        - "%kernel.root_dir%/../vendor/bundles/Liip/MagentoBundle/Resources/config/security_factories.xml"

    providers:
        magento:
            id: security.user.provider.magento

    firewalls:
        secured_area:
            pattern:    ^/
            anonymous: ~
            magento:
                provider:   magento
                check_path: /login_check
                login_path: /login
            logout:
                path:   /logout
                target: /
```


Usage
=====

Store Resolver
--------------

For accessing customer data we need the correct Magento store to be initialized. Magento loads
the default store which can be configured for the respective group. You can go to `System > Manage Stores`
then open the item which is in the «Store Name» row (which is actually the group) and select the
«Default Store View».

Store resolvers do figure out which Magento store to initialize. Whenever it can't determine a store
it keeps the default one. Also, if it fails setting the resolved store it goes back to the default store.


The default store resolver is `LocaleStore` which uses the symfony locale as store code. In case your
store codes do not match the locales you need to use the `LocaleStoreResolver` which allows you to
configure the mapping with `store_mappings` of the locale to the store code. This also helps if you need
multiple fallback stores depending on the locale.

For more customized resolvers you may also write your own by implementing `StoreResolverInterface`. In
that case the default resolver service can be overritten as follows:

```
# app/config/config.yml
liip_magento:
    service:
        store_resolver: my.store_resolver.id
```


Retrieving Data and HTML from Magento
-------------------------------------

In this demo we load the footer block and the number of items in the Magento cart

```
class MagentoController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction()
    {
        $block = \Mage::getSingleton('core/layout');
        $footer = $block->createBlock('page/html_footer');
        $footer->setTemplate('page/html/footer.phtml');

        $cart = \Mage::helper('checkout/cart')->getCart()->getItemsCount();
        return array('cart' => $cart, 'footer' => $footer->toHTML());
    }
}
```

Template-snippet for the demo:

```
{% block content %}
    
    You have {{ cart }} items in your cart!
    
    {{ footer | raw}}
{% endblock %}  
```

Listening to Magento events
===========================

Using the [Magento Symfony module](https://github.com/pulse00/Magento-Symfony-Module) you can listen to events dispatched by Magento
inside your Symfony application.

Here's an example to handle the `customer_address_save_after` event, e.g.
to synchronize your CRM backend with Magento customers:

Register your listener in Symfony

```yaml
services:
    acme_demo_bundle.customer_save_after: 
        class: %acme_demo_bundle.customer_save_after.class%
        arguments: [ @some_service_id ]
        tags:
            - { name: kernel.event_listener, event: mage.customer_save_after, method: synchronize }
```

Dispatch the event in Magento:

```XML
<?xml version="1.0"?>
<config>
    <modules>
        <MyModule_Core>
            <version>0.1.0</version>
        </MyModule_Core>
    </modules> 
    <global>    
      <events>
          <customer_address_save_after>
              <observers>
                  <address_update>
                      <type>singleton</type>
                      <class>MyModule_Core_Customer_Synchronizer</class>
                      <method>synchronize</method>
                  </address_update>
              </observers>
          </customer_address_save_after>
      </events>
    </global>
</config>
```

```php
<?php 
class MyModule_Core_Customer_Synchronizer
{
    
    public function synchronize(Varien_Event_Observer $observer) 
    {                
        $mageEvent = $observer->getEvent();
        $symfonyEvent = new MageEvent($mageEvent);        
        $container = Mage::getSingleton('Symfony_Core_DependencyInjection_Container');
        $container->get('event_dispatcher')->dispatch('mage.customer_save_after', $symfonyEvent)        
        
    }
}
```


More configuration examples
===========================

## Example 1 - Run magento on a subdomain

- Create 2 virtual hosts, one for Symfony and one for Magento, e.g. `mysite.local` and `shop.mysite.local`:

```
Apache example:

<VirtualHost *:80>
    DocumentRoot "/var/www/mysite/symfony/web"

    ServerName mysite.local

    <Directory "/var/www/mysite/symfony/web">
        AllowOverride All
    </Directory>

</VirtualHost>

<VirtualHost *:80>
    DocumentRoot "/var/www/mysite/magento"

    ServerName shop.mysite.local

    <Directory "/var/www/mysite/magento">
        AllowOverride All
    </Directory>

</VirtualHost>
```
- Setup Magento (e.g. in ../project/magento), and configure the cookie path to `/` and the cookie domain to `.local`
- Setup Symfony (e.g. in ../project/symfony) and the LiipMagentoBundle 
- Add the `login` and `login_check` routes and setup the login form, see the [Symfony docs](http://symfony.com/doc/current/book/security.html#using-a-traditional-login-form)

After that, you should have synced sessions between `mysite.local` and `shop.mysite.local` 
meaning that logging in/out on either side will login/logout the user on the opposite side.

## Example 2 - Run magento as an alias (e.g. mysite.com/shop)

- Create 1 virtual hosts and add an alias for Magento :

```
Apache example: 

<VirtualHost *:80>
    DocumentRoot "/var/www/mysite/symfony/web"

    ServerName mysite.local

    Alias /shop /var/www/mysite/magento

    <Directory "/var/www/mysite/symfony/web">
        AllowOverride All
    </Directory>

    <Directory "/var/www/mysite/magento">
        AllowOverride All
    </Directory>
</VirtualHost>

```
- Setup Magento (e.g. in ../project/magento), and configure the cookie path to `/` and the cookie domain to `.local`
- Setup Symfony (e.g. in ../project/symfony) and the LiipMagentoBundle 
- Add the `login` and `login_check` routes and setup the login form see the [Symfony docs](http://symfony.com/doc/current/book/security.html#using-a-traditional-login-form)

After that, you should have synced sessions between `mysite.local` and `mysite.local/shop` 
meaning that logging in/out on either side will login/logout the user on the opposite side.
