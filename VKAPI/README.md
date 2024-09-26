# VK API Compatability layer for OpenVK

This directory contains VK API handlers, structures and relared
exceptions. It is still a work-in-progress functionality.  
**Note**: requests to API are routed through
openvk.Web.Presenters.VKAPIPresenter, this dir contains only handlers.

[Documentation for API clients](https://docs.ovk.to/openvk_engine/api/description/)

## Implementing API methods

VK API methods have names like this: `example.test`. To implement a
method like this you will need to create a class `Example` in the
Handlers subdirectory. This class **must** extend VKAPIHandler and be
final.  
Next step is to create test method. It **must** have a type hint that is
not void. Everything else is fine, the return value of method will be
authomatically converted to JSON and sent back to client.

### Parameters

Method arguments are parameters. To declare a parameter just create an
argument with the same name. You should also provide correct type hints
for them. Type conversion is done automatically if possible. If not
possible error №1 will be returned.  
If parameter is not passed by client then router will pass default value
to argument. If there is no default value but argument accepts NULL then
NULL will be passed. If NULL is not acceptable, default value is
undefined and parameter is not passed, API will return missing parameter
error to client.

### Returning errors

To return an error, call fail method like this: `$this->fail(5,
"error")` (first argument is error code and second is error message).
You can also throw the exception manually: `throw new
APIErrorException("error", 5)` (class:
openvk.VKAPI.Exceptions.APIErrorException).  
If you throw any exception that does not inherit APIErrorException then
API will return error №1 (unknown error) to client.

### Refering to user

To get user use `getUser` method: `$this->getUser()`. Keep in mind it
will return NULL if user is undefined (no access\_token passed or it is
invalid/expired or roaming authentification failed).  
If you need to check whether user is defined use `userAuthorized`. This
method returns true if user is present and false if not.  
If your method can’t work without user context call `requireUser` and it
will automatically return unauthorized error.

### Working with data

You can use OpenVK models for that. However, **do not** return them
(either you will leak data or JSON conversion will fail). It is better
to create a response object and return it. It is also a good idea to
define a structure in Structures subdirectory.

Have a lot of fun <sup></sup>
