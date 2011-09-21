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
    $ patch -p0 --dry-run < $LIIP_MAGENTO_BUNDLE_DIR/magento-autoloader.patch
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
        magento: ~
    
    firewalls:
        secured_area:
            pattern:    ^/secured/
            magento:
                provider:   magento
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