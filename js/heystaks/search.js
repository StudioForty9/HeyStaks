document.observe("dom:loaded", function() {
    $$('.category-products .item a:not(".link-wishlist, .link-compare")').each(function (element) {
        element.observe('click', function (e) {
            e.stop();
            var resultsPerPage = $$('.item').length;
			if (typeof document.URL.toQueryParams().p != 'undefined'){
				var page = parseInt(document.URL.toQueryParams().p);
			}else{
				var page = 1;
			}
            var position = (element.up('li').previousSiblings().size() + 1) + ((page - 1) * resultsPerPage);
            window.location = element.href + '?referer=heystaks_search_results&position=' + position;
        });
    });
});