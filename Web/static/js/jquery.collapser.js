/* 
 * jQuery - Collapser plugin v3.0.1
 * https://www.aakashweb.com/
 * Copyright 2020, Aakash Chakravarthy
 * Released under the MIT License.
 */

;(function ($, window, document, undefined) {
    
    var name = "collapser",
        defaults = {
            mode: 'words',
            speed: 'fast',
            truncate: 50,
            ellipsis: ' ... ',
            controlBtn: null,
            showText: tr("show_more"),
            hideText: tr("hide_collapsed"),
            showClass: 'show-class',
            hideClass: 'hide-class',
            atStart: 'hide',
            blockTarget: 'next',
            blockEffect: 'fade',
            lockHide: false,
            changeText: false,
            beforeShow: null,
            afterShow: null,
            beforeHide: null,
            afterHide: null
        };

    // Constructor
    function Collapser(el, options) {
        
        var s = this; // The collapser class object
        
        s.o = $.extend({}, defaults, options);
        s.e = $(el); // The element to collapse
        s.init();

    }
    
    Collapser.prototype = {

        init: function(){
        
            var s = this;
            
            s.mode = s.o.mode;
            s.remaining = null;
            s.ctrlButton = $.isFunction(s.o.controlBtn) ? s.o.controlBtn.call(s.e) : $('<a href="#" data-ctrl></a>');

            if(s.mode == 'lines'){
                s.e.wrapInner('<div>');
            }

            // Get the start type of the target element and activate the collapse
            var atStart = $.isFunction(s.o.atStart) ? s.o.atStart.call(s.e) : s.o.atStart;
            atStart = (typeof s.e.attr('data-start') !== 'undefined') ? s.e.attr('data-start') : atStart;
            
            if(atStart == 'hide'){
                s.hide(0);
            }else{
                s.show(0);
            }
            
        },
        
        // SHOW METHOD
        show: function(speed){

            var s = this;
            var e = s.e;

            s.collapsed = false;

            if(typeof speed === 'undefined') speed = s.o.speed;

            if($.isFunction(s.o.beforeShow))
                s.o.beforeShow.call(s.e, s);
            
            var afterShow = function(){
                if($.isFunction(s.o.afterShow))
                    s.o.afterShow.call(s.e, s);
            };

            e.find('[data-ctrl]').remove();
            
            // Modes chars, words and lines follow the same sequence to show the collapsed data.
            if(s.mode == 'block'){

                s.blockMode(e, 'show', speed, afterShow);

            }else{

                /*
                    1. Get the current height when collapsed.
                    2. Set the expanded content and get the height.
                    3. Animate the element height from collapsed height to the expanded height.
                */

                var target = (s.mode == 'lines') ? e.children('div') : e; // For lines mode, the element to collapse is the inner wrapper div
                var oldHeight = target.height();

                if(s.mode == 'lines'){
                    target.height('auto');
                }else{
                    var backupHTML = target.data('collHTML');
                    if(typeof backupHTML !== 'undefined'){
                        target.html(backupHTML);
                    }
                }

                var newHeight = target.height();

                target.height(oldHeight);
                target.animate({
                    'height': newHeight
                }, speed, function(){
                    target.height('auto');
                    afterShow();
                });

                e.removeClass(s.o.hideClass).addClass(s.o.showClass);

                // Add the control button and set the display text
                if(!$.isFunction(s.o.controlBtn)){
                    e.append(s.ctrlButton);
                }

                s.ctrlButton.html(s.o.hideText);

            }

            // Bind the click event for all the modes
            s.bindEvent();

            // Remove the control button if option is to hide it
            if(s.o.lockHide){
                s.ctrlButton.remove();
            }

        },
        
        // HIDE METHOD
        hide: function(speed){

            var s = this;
            var e = s.e;

            s.collapsed = true;

            if(typeof speed === 'undefined') speed = s.o.speed;

            if($.isFunction(s.o.beforeHide)){
                s.o.beforeHide.call(s.e, s);
            }

            var afterHide = function(){
                if($.isFunction(s.o.afterHide))
                    s.o.afterHide.call(s.e, s);
            };

            e.find('[data-ctrl]').remove();

            // Mode - chars & words
            if(s.mode == 'chars' || s.mode == 'words'){

                var fullHTML = e.html();
                var collapsedHTML = s.getCollapsedHTML(fullHTML, s.mode, s.o.truncate) // returns false if content is very small and cannot collapse.

                if(collapsedHTML){
                    var plainText = e.text();
                    s.remaining = plainText.split(s.mode == 'words' ? ' ' : '').length - s.o.truncate;

                    e.data('collHTML', fullHTML);
                    e.html(collapsedHTML);
                }else{
                    s.remaining = 0;
                }

            }

            // Mode - lines
            if(s.mode == 'lines'){

                var $wrapElement = e.children('div');
                var originalHeight = $wrapElement.outerHeight();
                var $lhChar = $wrapElement.find('[data-col-char]');

                if($lhChar.length == 0){
                    var $lhChar = $('<span style="display:none" data-col-char>.</span>');
                    $wrapElement.prepend($lhChar);
                }

                var lineHeight = $lhChar.height();
                var newHeight = (lineHeight * s.o.truncate) + lineHeight/4; // Adding quarter of line height to avoid cutting the line.

                // If content is already small and criteria is already met then no need to collapse.
                if(newHeight >= originalHeight){
                    newHeight = 'auto';
                    s.remaining = 0;
                }else{
                    s.remaining = parseInt(Math.ceil((originalHeight - newHeight)/lineHeight));
                }

                $wrapElement.css({
                    'overflow': 'hidden',
                    'height': newHeight
                });

            }

            // Mode - block
            if(s.mode == 'block'){
                s.blockMode(e, 'hide', speed, afterHide);
            }

            afterHide();

            if(s.mode != 'block'){

                e.removeClass(s.o.showClass).addClass(s.o.hideClass);

                // Add the control button and set the display text
                if(!$.isFunction(s.o.controlBtn) && s.remaining > 0){
                    e.append(s.ctrlButton);
                }

                s.ctrlButton.html(s.o.showText);
            }

            // Bind the click event for all the modes
            s.bindEvent();

        },

        blockMode: function(e, type, speed, callback){
            var s = this
            var effects = ['fadeOut', 'slideUp', 'fadeIn', 'slideDown'];
            var inc = (s.o.blockEffect == 'fade') ? 0 : 1;
            var effect = (type == 'hide') ? effects[inc] : effects [inc + 2];
            
            if(!$.isFunction(s.o.blockTarget)){
                if($.fn[s.o.blockTarget])
                    $(e)[s.o.blockTarget]()[effect](speed, callback);
            }else{
                s.o.blockTarget.call(s.e)[effect](speed, callback);
            }
            
            if(type == 'show'){
                e.removeClass(s.o.showClass).addClass(s.o.hideClass);
                if(s.o.changeText)
                    e.text(s.o.hideText);
            }else{
                e.removeClass(s.o.hideClass).addClass(s.o.showClass);
                if(s.o.changeText)
                    e.text(s.o.showText);
            }
            
        },

        getCollapsedHTML: function(fullHTML, mode, truncateAt){
            var inTag = false;
            var itemsFound = 0;
            var slicePoint = 0;
            var hasLessItems = true;

            // Iterate over the full HTML and find the point to break the HTML.
            for(var i = 0; i <= fullHTML.length; i++){

                char = fullHTML.charAt(i);
                if(char == '<') inTag = true;
                if(char == '>') inTag = false;
                
                if(itemsFound == truncateAt){
                    slicePoint = i;
                    hasLessItems = false;
                    break;
                }

                if(!inTag){
                    if(mode == 'words' && char == ' '){
                        itemsFound++;
                    }
                    if(mode == 'chars'){
                        itemsFound++;
                    }
                }

            }

            if(hasLessItems)
                return false;

            var slicedHTML = fullHTML.slice(0, slicePoint);
            var balancedHTML = this.balanceTags(slicedHTML);

            return balancedHTML + '<span class="coll-ellipsis">' + this.o.ellipsis + '</span>';
        },

        balanceTags: function(string){
            // Thanks to https://osric.com/chris/javascript/balance-tags.html

            if (string.lastIndexOf("<") > string.lastIndexOf(">")) {
                string = string.substring(0, string.lastIndexOf("<"));
            }

            var tags = string.match(/<[^>]+>/g);
            var stack = new Array();
            for (tag in tags) {
                if (tags[tag].search("/") <= 0) {
                    stack.push(tags[tag]);
                } else if (tags[tag].search("/") == 1) {
                    stack.pop();
                } else {
                }
            }

            while (stack.length > 0) {
                var endTag = stack.pop();
                endTag = endTag.substring(1,endTag.search(/[>]/));
                string += "</" + endTag + ">";
            }

            return(string);
        },

        bindEvent: function(){
            var s = this;
            var target = (s.mode == 'block') ? s.e : s.ctrlButton; // If mode is block, then the selector itself is the target not the control button

            target.off('click').on('click', function(event){
                event.preventDefault();
                if(s.collapsed){
                    s.show();
                }else{
                    s.hide();
                }
            });
        }

    };

    // Attach the object to the DOM
    $.fn[name] = function (options) {
        return this.each(function () {
            if (!$.data(this, name)) {
                $.data(this, name, new Collapser(this, options));
            }
        });
    };

})(jQuery, window, document);