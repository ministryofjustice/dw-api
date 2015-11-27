DW API
==========

WordPress plugin that lets you query the page structure through a RESTful API

##Endpoints
These can be accessed using the form

	/{plugin-root}/{endpoint}/{param_1}/.../{param_n}/

The default root is _service_ but this can be changed in the code by altering the DWAPI_ROOT constant.

* children/{page-id}/ - retrieve the immediate children of the given page

* az/{type}/{category}/{plus+seperated+keywords}/{initial}/{page}/{per-page} - retrieve items grouped by initial

* news/{type}/{category}/{plus+seperated+keywords}/{initial}/{page}/{per-page} - retrieve news items

* post/{type}/{category}/{plus+seperated+keywords}/{initial}/{page}/{per-page} - retrieve blog posts

* search/{type}/{category}/{plus+seperated+keywords}/{page}/{per-page} - searches all site content

* events/{date}/{keywords}/{page}/{per-page} - searches events

* months/{post-type} - produces list of months (by default 12) with post count

* crawl/ - returns pages that have a redirect url

* likes/{post_id} - increment like counter for that post (any type)

##Direct use
You can use the plugin within by PHP as follows:

* $children = new children_request({page-id})

* $az = new az_request ({type},{category},{plus+seperated+keywords},{initial},{page},{per-page})

* $news = new news_request ({type},{category},{plus+seperated+keywords},{initial},{page},{per-page})

* $posts = new post_request ({type},{category},{plus+seperated+keywords},{initial},{page},{per-page})

* $search = new search_request ({type},{category},{plus+seperated+keywords},{page},{per-page})

* $events = new events_request ({date},{keywords},{page},{per-page})

* $months = new months_request ({post-type})

* $crawl = new crawl_request

* $likes = new likes_request ({post-id})

##Release history

0.16   - added incrementor template and likes_request endpoint

0.15   - added post_request class to return blog posts

0.14   - added months_class to return count of posts by month (up to 12 months from current date)

0.13   - added event_request class to handle event search requests

0.12   - modify children_request to ignore text before colon in title

0.11   - rebranded to DW API & added cache control header

0.10.1 - removed CORS header

0.10   - children_request now returns top level items with is_top_level set to 1 if
        no parent id is given (or it is set to 0)

0.9.1  - fixed issue which was preventing news appearing with Relevanssi enabled

0.9    - added crawl_request to provide url mapping for content crawler/importer

0.8    - extended children_request to return child_count and is_external

0.7.1  - added file_name to returned json for search_request

0.7    - extended search_request so it can be called on its own

0.6.1  - news_request date filter now handles day and month without leading zeroes
        reports error if date components are non-numeric

0.6    - added ability to filter by year/month/day on news_request

0.5.1  - fix for news_request returning non-news items
        'news' is now also an allowed 'type' for az_request

0.5    - added news_request

0.4    - added az_request and refactored search_request

0.3.1  - corrected issue when api_request class instantiated directly in PHP
        (note that api_request now takes array as argument which mirrors API args)

0.3    - added urlParams and totalResults to search API; handles '-' in query URL

0.2    - search API added

0.1    - initial release with _children_ endpoint
