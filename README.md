MagentoBundle
=============

Integrate Symfony2 applications with Magento

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

7.  Use the Magento session storage

    ````
    framework:
        session:
            storage_id: liip_magento.session.storage
    ````


Configuration
============

```
# app/config/config.yml

liip_magento:
# path to the Mage.php file
    mage_file:  %kernel.root_dir%/../../magento/app/Mage.php
# the default store code (or id) if no store code found
    default_store: de
# only for a few store resolvers, mapping to store code
    store_mappings:
        de: de
        en: en
        fr: en
```

Usage
=====

Store Resolver
--------------

The default store resolver is `LocaleStore` which uses the symfony locale as 
store code.

Others are provided such as `LocaleStoreResolver` which can be configured to map
locales to specific store codes. You may also write your own by implementing `StoreResolverInterface`.

