if (!window.HeyStaks) {
    var HeyStaks = new Object();
}

HeyStaks.feedback = Class.create();
HeyStaks.feedback.prototype = {
    initialize: function () {
        new Ajax.Request('/heystaks/ajax/init', {method: 'post'});
    },
    send: function () {
        var action = this._getAction();

        new Ajax.Request(
            '/heystaks/ajax/feedback',
            {
                method: 'post',
                parameters: {
                    action: action
                }
            }
        );
    },
    _getAction: function () {
        var action = 'BROWSE';

        if($$('body')[0].hasClassName('catalog-product-view') &&
            document.referrer.split('/')[3] &&
            document.referrer.split('/')[4]){
            action = 'SELECT';
        }

        return action;
    }

};

document.observe('dom:loaded', function(){
    var feedback = new HeyStaks.feedback();
    feedback.send();
});