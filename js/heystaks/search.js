document.observe("dom:loaded", function() {
    $$('.category-products ol.products-list a:not(".link-wishlist, .link-compare")').each(function (element) {
        element.observe('click', function (e) {
            e.stop();
            var resultsPerPage = parseInt($$('.toolbar .pager .limiter select option:selected')[0].innerHTML);
            var page = parseInt(document.URL.toQueryParams().p);
            var position = (element.up('li').previousSiblings().size() + 1) + (page * resultsPerPage);
            window.location = element.href + '?referer=heystaks_search_results&position=' + position;
        });
    });
});