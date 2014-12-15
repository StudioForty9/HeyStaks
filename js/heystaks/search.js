document.observe("dom:loaded", function() {
    $$('.category-products ol a:not(".link-wishlist, .link-compare")').each(function (element) {
        element.observe('click', function (e) {
            e.stop();
            window.location = element.href + '?referer=heystaks_search_results';
        });
    });
});