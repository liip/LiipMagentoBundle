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
    
    ```
    # app/config/config.yml
    liip_magento:
        mage_file:  %kernel.root_dir%/../../magento/app/Mage.php
    ...
    ```

6.  Fix the Magento autoloader

    ```
    $ cd $MAGENTO_DIR
    $ patch -p0 --dry-run < $LIIP_MAGENTO_BUNDLE_DIR/magento-autoloader.patch
    ```


Usage
=====

...


