# Server Side Matomo Tracking Bundle

This is a bundle that helps to activate server side matomo tracking for Pimcore and the Pimcore E-Commerce Framework. 

To configure and activate the tracking follow the instructions: 

### Activating

Activate the bundle by activating it in Pimcore extension manager. 

### Configuring 

#### Configuring Tracker

To configure a tracker, just register a service on the Symfony container as follows. Important is to
add the `` tag to the service and the class has to be (or a sub class of) `Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\Tracker`. 

```yml 
    my_serverside_tracker:
        class: Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\Tracker
        arguments:
            - 33  # matomo site id
            - https://my-endpoint.com/matomo # matomo service url
            - default  # pimcore site id (default is 'default') 
        tags: [ pimcore.serverside_matomo_tracking.tracker ]
```

- To disable cookies for a tracker, use the `disableCookieSupport` call like in the following example.
- To use the client IP (instead of the server IP) for tracking, you have to set a token auth of a matomo super user 
  (in order to be allowed to overwrite the IP address). 

```yml 
    my_serverside_tracker:
        class: Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\Tracker
        arguments:
            - 33  # matomo site id
            - https://my-endpoint.com/matomo # matomo service url
            - default  # pimcore site id (default is 'default') 
        tags: [ pimcore.serverside_matomo_tracking.tracker ]
        calls:
            - [disableCookieSupport]
            - [setTokenAuth, ['<MY_SECRET_MATOMO_TOKEN>']]        
```


#### Configuring E-Commerce Tracking
  
To configure e-commerce tracking an additional e-commerce tracker has to be configured to the container and 
the e-commerce framework configuration has to be extended: 


```yml
    my_ecommerce_framework_matomo_tracker:
          class: Pimcore\Bundle\ServerSideMatomoTrackingBundle\Tracking\EcommerceFramework\ServerSideMatomoTracker
          arguments:
              - '@my_serverside_tracker'  # id of server side matomo tracker
``` 

```yml
pimcore_ecommerce_framework:
    tracking_manager:
        trackers:
            serverside_matomo_tracking:
                id: my_ecommerce_framework_matomo_tracker   # id of matomo e-commerce tracker 
                # Service id for item builder for tracker
                item_builder_id: AppBundle\Ecommerce\Tracking\TrackingItemBuilder  
                enabled: true
```

