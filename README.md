
# Introduction

This Package provides a configurable 404 error page for TYPO3 Neos with dimensions support.


# Installation

you need to add the package to your `composer.json`

``` bash
{
    "require": {
        "techdivision/notfound": "1.0.*"
    },
}
```

Install the package:

``` bash
composer update techdivision/notfound
```


# Configuration

to enable this package you need to set the following settings in your Site or Project Settings.yaml

``` bash
TechDivision:
  NotFound:
    enable: true
    defaultUriSegment: '404'
```

`enable`: enables or disables the module
`defaultUriSegment`: the node path uri segment of your 404 site



## Optional

if you are using a **defaultUriSuffix** (in most cases `.html`) you need to add a additional route in your Routes.yaml
at the **end** of your Routes.yaml

``` bash
##
# TechDivision.NotFound Subroutes - only required if the 'TYPO3 Neos' route has a 'defaultUriSuffix' suffix
##
-
  name: 'TechDivision.NotFound'
  uriPattern: '<TechDivisionNotFoundSubroutes>'
  subRoutes:
    'TechDivisionNotFoundSubroutes':
      package: 'TechDivision.NotFound'
```



