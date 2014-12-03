dw-pageapi
==========

WordPress plugin that lets you query the page structure through a RESTful API

##Endpoints
These can be accessed using the form 

	/{plugin-root}/{endpoint}/{param_1}/.../{param_n}/

The default root is _service_ but this can be changed in the code by altering the PAGEAPI_ROOT constant.

* children/{page-id}/ - retrieve the immediate children of the given page

* search/{type}/{category}/{plus+seperated+keywords}/{initial}/{page}/{per-page}

##Direct use
You can use the plugin within by PHP as follows:

* $children = new children_request({page-id})

* $search = new search_request ({type},{category},{plus+seperated+keywords},{initial},{page},{per-page})

##Release history

0.1   - initial release with _children_ endpoint

0.2   - search API added

0.3   - added urlParams and totalResults to search API; handles '-' in query URL

0.3.1 - corrected issue when api_request class instantiated directly in PHP
	(note that api_request now takes array as argument which mirrors API args)
