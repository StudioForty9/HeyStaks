Varien.searchForm.prototype._selectAutocompleteItem = Varien.searchForm.prototype._selectAutocompleteItem.wrap(function(parentMethod, element){
    var url = Element.readAttribute(element, 'url');
    if(url) {
        window.location = url;
    }else{
        parentMethod();
    }
});