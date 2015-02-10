document.observe("dom:loaded", function() {
    $$('.category-products ol a:not(".link-wishlist, .link-compare")').each(function (element) {
        element.observe('click', function (e) {
            e.stop();
            var position = element.up('li').previousSiblings().size() + 1;
            window.location = element.href + '?referer=heystaks_search_results&position=' + position;
        });
    });
});